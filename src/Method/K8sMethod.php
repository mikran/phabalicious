<?php

namespace Phabalicious\Method;

use Phabalicious\Configuration\ConfigurationService;
use Phabalicious\Configuration\HostConfig;
use Phabalicious\Exception\FabfileNotReadableException;
use Phabalicious\Exception\FailedShellCommandException;
use Phabalicious\Exception\MismatchedVersionException;
use Phabalicious\Exception\MissingScriptCallbackImplementation;
use Phabalicious\Exception\UnknownReplacementPatternException;
use Phabalicious\Exception\ValidationFailedException;
use Phabalicious\Scaffolder\Options;
use Phabalicious\Scaffolder\Scaffolder;
use Phabalicious\ShellProvider\LocalShellProvider;
use Phabalicious\ShellProvider\ShellProviderInterface;
use Phabalicious\Utilities\Utilities;
use Phabalicious\Validation\ValidationErrorBagInterface;
use Phabalicious\Validation\ValidationService;

class K8sMethod extends BaseMethod implements MethodInterface
{
    const AVAILABLE_SUB_COMMANDS = [
        'delete',
        'apply',
        'scaffold',
        'kubectl',
        'get-available-subcommands'
    ];

    /** @var ShellProviderInterface */
    protected $kubectlShell;

    public function getName(): string
    {
        return 'k8s';
    }

    public function supports(string $method_name): bool
    {
        return $method_name === 'k8s';
    }

    public function getGlobalSettings(): array
    {
        return [
            'kube' => [
                'scaffolder' => [
                    'baseUrl' => 'https://config.factorial.io/scaffold/kube',
                ],
            ],
            'executables' => [
                'kubectl' => 'kubectl',
            ],
        ];
    }

    public function getDefaultConfig(ConfigurationService $configuration_service, array $host_config): array
    {
        $slug = Utilities::slugify($configuration_service->getSetting('name') . '-' . $host_config['type'], '-');
        $default = parent::getDefaultConfig($configuration_service, $host_config);
        // $default['shellProvider'] = 'k8s';
        $global_settings = $configuration_service->getSetting('kube');
        $default['shellProvider'] = 'kubectl';

        $default['kube'] = Utilities::mergeData($global_settings, [
            'scaffoldBeforeDeploy' => true,
            'projectFolder' => 'kube',
            'namespace' => 'default',
            'applyCommand' => 'apply -k .',
            'deleteCommand' => 'delete -k .',
            'scaffolder' => [
                'template' => 'simple/index.yml',
            ],
            'parameters' => [
                'projectSlug' => $slug,
                'name' => $slug,
            ],
        ]);

        return $default;
    }

    public function validateConfig(array $config, ValidationErrorBagInterface $errors)
    {
        parent::validateConfig($config, $errors); // TODO: Change the autogenerated stub
        $service = new ValidationService($config, $errors, 'Host-config ' . $config['configName']);
        $service->isArray('kube', 'the k8s method needs a kube config array');
        if (!empty($config['kube'])) {
            $service = new ValidationService($config['kube'], $errors, 'kube config of ' . $config['configName']);
            $service->hasKey('projectFolder', 'Provide a folder-name where the yml files can be found.');
            $service->hasKey('deleteCommand', 'Provide a delte command which gets executed on deletion.');
            $service->hasKey('applyCommand', 'Provide a applyCommand which gets executed on apply.');
            $service->hasKey('scaffolder', '`scaffolder` is missing.');
            $service->isArray('parameters', '`parameters` needs to be an array.');
            if (!empty($config['kube']['parameters'])) {
                $service = new ValidationService(
                    $config['kube']['parameters'],
                    $errors,
                    'kube.parameters config of ' . $config['configName']
                );
                $service->hasKey('name', '`name` is missing.');
            }
        }
    }
    public function preflightTask(string $task, HostConfig $config, TaskContextInterface $context)
    {
        parent::preflightTask($task, $config, $context); // TODO: Change the autogenerated stub
        if (empty($config['kube']['podForCli'])) {
            $config->setChild('kube', 'podForCli', $this->getPodNameFromSelector($config, $context));
        }
    }

    protected function getPodNameFromSelector(HostConfig $host_config, TaskContextInterface $context)
    {
        $pod_selectors = $host_config['kube']['podSelector'];
        $replacements = Utilities::expandVariables([
            'host' => $host_config->raw(),
            'context' => $context->getData(),
        ]);
        $pod_selectors = Utilities::expandStrings($pod_selectors, $replacements);
        $result = $this->kubectl(
            $host_config,
            $context,
            sprintf(
                'get pods --namespace %s -l %s -o json',
                $host_config['kube']['namespace'],
                implode(',', $pod_selectors)
            ),
            true
        );

        $content = implode("\n", $result->getOutput());
        $data = json_decode($content, JSON_OBJECT_AS_ARRAY);

        $pod_name = $data['items'][0]['metadata']['name'] ?? null;
        if (empty($pod_name)) {
            $this->logger->warning(
                sprintf(
                    'Could not get pod name from provided selectors: `%s`',
                    implode("`, `", $pod_selectors)
                )
            );
        }
        return $pod_name;
    }

    public function deployPrepare(HostConfig $host_config, TaskContextInterface $context)
    {
        $this->apply($host_config, $context);
    }

    /**
     * @param HostConfig $host_config
     * @param TaskContextInterface $context
     * @throws FabfileNotReadableException
     * @throws FailedShellCommandException
     * @throws MismatchedVersionException
     * @throws MissingScriptCallbackImplementation
     * @throws UnknownReplacementPatternException
     * @throws ValidationFailedException
     */
    protected function scaffold(HostConfig $host_config, TaskContextInterface $context): void
    {
        $kube_config = $host_config['kube'];
        $host_data = $host_config->raw();
        $kube_config['parameters']['projectFolder'] = $kube_config['projectFolder'];
        $kube_config['parameters']['host'] = $host_data;
        $kube_config['parameters']['namespace'] = $kube_config['namespace'];

        if (empty($kube_config['scaffolder'])) {
            return;
        }
        $configuration = $context->getConfigurationService();

        $scaffold_url = $kube_config['scaffolder']['baseUrl'] . '/' . $kube_config['scaffolder']['template'];
        $scaffolder = new Scaffolder($configuration);

        $options = new Options();
        $options
            ->setAllowOverride(true)
            ->setUseCacheTokens(false)
            ->setSkipSubfolder(true)
            ->addVariable('host', $host_config->raw())
            ->addVariable('context', $context->getData());

        $scaffolder->scaffold(
            $scaffold_url,
            $kube_config['projectFolder'],
            $context,
            $kube_config['parameters'],
            $options
        );
    }

    public function kubectl(HostConfig $host_config, TaskContextInterface $context, $command, $capture_output = false)
    {
        $kube_config = $host_config['kube'];

        if (!$this->kubectlShell) {
            $this->kubectlShell = new LocalShellProvider($this->logger);
            $this->kubectlShell->setHostConfig($host_config);
        }

        $project_folder = $context->getConfigurationService()->getFabfilePath() . '/' . $kube_config['projectFolder'];

        $this->kubectlShell->pushWorkingDir($project_folder);
        $result = $this->kubectlShell->run(sprintf(
            '#!kubectl %s --namespace %s',
            $command,
            $kube_config['namespace']
        ), $capture_output);
        $this->kubectlShell->popWorkingDir();
        return $result;
    }


    public function k8s(HostConfig $host_config, TaskContextInterface $context)
    {
        $command = explode(' ', $context->get('command'));

        $subcommand = array_shift($command);
        $arguments = implode(' ', $command);

        if (!in_array($subcommand, self::AVAILABLE_SUB_COMMANDS)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Unknown k8s subcommand `%s`, allowed are: `%s`",
                    $subcommand,
                    implode("`, `", self::AVAILABLE_SUB_COMMANDS)
                )
            );
        }

        if (method_exists($this, $subcommand)) {
            return $this->{$subcommand}($host_config, $context, $arguments);
        }
    }

    public function delete(HostConfig $host_config, TaskContextInterface $context)
    {
        $this->kubectl($host_config, $context, $host_config['kube']['deleteCommand']);
    }

    public function apply(HostConfig $host_config, TaskContextInterface $context)
    {
        $kube_config = $host_config['kube'];
        if ($kube_config['scaffoldBeforeDeploy']) {
            $this->scaffold($host_config, $context);
        }
        $this->kubectl($host_config, $context, $kube_config['applyCommand']);
    }
}
