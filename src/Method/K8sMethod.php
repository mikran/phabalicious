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
use Phabalicious\ShellProvider\KubectlShellProvider;
use Phabalicious\ShellProvider\LocalShellProvider;
use Phabalicious\ShellProvider\ShellProviderInterface;
use Phabalicious\Utilities\Utilities;
use Phabalicious\Validation\ValidationErrorBagInterface;
use Phabalicious\Validation\ValidationService;

class K8sMethod extends BaseMethod implements MethodInterface
{
    const KUBECTL_SCRIPT_CONTEXT = 'kubectl';

    const AVAILABLE_SUB_COMMANDS = [
        'delete',
        'apply',
        'scaffold',
        'kubectl',
        'rollout',
        'logs',
        'describe',
        'get-available-subcommands'
    ];

    protected $contextStack = [];

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
            'context' => false,
            'kubeconfig' => false,
            'environment' => [],
            'scaffoldBeforeApply' => true,
            'applyBeforeDeploy' => true,
            'waitAfterApply' => true,
            'projectFolder' => 'kube',
            'namespace' => 'default',
            'applyCommand' => 'apply -k .',
            'deleteCommand' => 'delete -k .',
            'deployments' => [
                '%host.kube.parameters.name%'
            ],
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
            $service->hasKey('kubeconfig', 'Path to a kubeconfig file.');
            $service->hasKey('projectFolder', 'Provide a folder-name where the yml files can be found.');
            $service->hasKey('deleteCommand', 'Provide a delte command which gets executed on deletion.');
            $service->hasKey('applyCommand', 'Provide a applyCommand which gets executed on apply.');
            $service->hasKey('scaffolder', '`scaffolder` is missing.');
            $service->isArray('environment', 'the environment for kubectl needs to be an array');
            $service->isArray('deployments', 'phab needs a list of deployment names, so it can check their status.');
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

    public function alterConfig(ConfigurationService $configuration_service, array &$data)
    {
        parent::alterConfig($configuration_service, $data); // TODO: Change the autogenerated stub

        $replacements = Utilities::expandVariables([
            'globals' => Utilities::getGlobalReplacements($configuration_service),
            'settings' => $configuration_service->getAllSettings(),
            'host' => $data,
        ]);

        $data = Utilities::expandStrings($data, $replacements, ['podSelector', 'deployments']);

        $data['kubectlOptionsCombined'] = trim(implode(
            ' ',
            KubectlShellProvider::getKubectlCmd($data, '')
        ));
    }

    /**
     * Get the kubectl cmd with all options.
     *
     * @param HostConfig $config
     * @param string $arg
     * @return string
     */
    protected function expandCmd(HostConfig $config, $arg = '')
    {
        $cmd = implode(' ', KubectlShellProvider::getKubectlCmd($config->raw(), '#!kubectl'));
        return $cmd . ' ' . $arg;
    }


    public function preflightTask(string $task, HostConfig $config, TaskContextInterface $context)
    {
        parent::preflightTask($task, $config, $context);

        if (empty($config['kube']['podForCli'])) {
            $config->setChild('kube', 'podForCli', $this->getPodNameFromSelector($config, $context));
            $config->setChild('kube', 'podForCliSet', true);
        }
        $script_context = $context->get(ScriptMethod::SCRIPT_CONTEXT, 'host');
        if ($task == 'runScript' && $script_context == self::KUBECTL_SCRIPT_CONTEXT) {
            $context->set('rootFolder', $this->kubectlShell->getHostConfig()['rootFolder']);
            $this->ensureShell($config, $context);
            $context->setShell($this->kubectlShell);
        }
    }

    public function postflightTask(string $task, HostConfig $config, TaskContextInterface $context)
    {
        parent::postflightTask($task, $config, $context);
        if ($task == 'k8s' && !empty($config['kube']['podForCliSet'])) {
            $config->setChild('kube', 'podForCli', null);
            $config->setChild('kube', 'podForCliSet', null);
        }
    }

    protected function getPodNameFromSelector(HostConfig $host_config, TaskContextInterface $context)
    {
        $pod_selectors = $host_config['kube']['podSelector'];
        $pod_selectors = $this->expandReplacements($host_config, $context, $pod_selectors);

        $result = $this->kubectl(
            $host_config,
            $context,
            sprintf(
                'get pods -l %s -o json',
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
        if (!empty($host_config['kube']['applyBeforeDeploy'])) {
            $this->apply($host_config, $context);
        }
    }

    public function startRemoteAccess(HostConfig  $hostConfig, TaskContextInterface $context)
    {
        $this->ensureShell($hostConfig, $context);
        $context->setShell($this->kubectlShell);
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

        $this->ensureShell($host_config, $context);
        $scaffold_url = $kube_config['scaffolder']['baseUrl'] . '/' . $kube_config['scaffolder']['template'];
        $scaffolder = new Scaffolder($configuration);

        $options = new Options();
        $options
            ->setAllowOverride(true)
            ->setUseCacheTokens(false)
            ->setSkipSubfolder(true)
            ->addVariable('host', $host_config->raw())
            ->addVariable('context', $context->getData())
            ->setShell($this->kubectlShell);

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
        $project_folder = $this->ensureShell($host_config, $context);

        $this->kubectlShell->pushWorkingDir($project_folder);
        $result = $this->kubectlShell->run(sprintf(
            '%s %s --namespace %s',
            $this->expandCmd($host_config),
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
        if (!empty($kube_config['scaffoldBeforeApply'])) {
            $this->scaffold($host_config, $context);
        }

        $this->kubectl($host_config, $context, $kube_config['applyCommand']);


        if (!empty($kube_config['waitAfterApply'])) {
            $this->rollout($host_config, $context, 'status');
        }

        // Reset podForCliSet as the info will be obsolete.
        if (!empty($host_config['kube']['podForCliSet'])) {
            $host_config->setChild('kube', 'podForCli', null);
            $host_config->setChild('kube', 'podForCliSet', null);
        }
    }

    public function rollout(HostConfig $host_config, TaskContextInterface $context, $command)
    {
        $kube_config = $host_config['kube'];
        $deployments = $this->expandReplacements($host_config, $context, $kube_config['deployments']);
        foreach ($deployments as $deployment) {
            $this->kubectl($host_config, $context, "rollout $command deployments/$deployment");
        }
    }

    public function logs(HostConfig $host_config, TaskContextInterface $context, $additional_cmd)
    {
        $kube_config = $host_config['kube'];
        $this->kubectl($host_config, $context, sprintf("logs -f %s %s", $kube_config["podForCli"], $additional_cmd));
    }

    public function describe(HostConfig $host_config, TaskContextInterface $context, $additional_cmd)
    {
        $kube_config = $host_config['kube'];
        $this->kubectl(
            $host_config,
            $context,
            sprintf("describe pod %s %s", $kube_config["podForCli"], $additional_cmd)
        );
    }


    /**
     * @param HostConfig $host_config
     * @param TaskContextInterface $context
     * @return string
     * @throws FailedShellCommandException
     */
    protected function ensureShell(HostConfig $host_config, TaskContextInterface $context): string
    {
        $kube_config = $host_config['kube'];
        $project_folder = $context->getConfigurationService()->getFabfilePath() . '/' . $kube_config['projectFolder'];

        if (!$this->kubectlShell) {
            $this->kubectlShell = new LocalShellProvider($this->logger);
            $shell_host_config = new HostConfig([
                'rootFolder' => realpath(dirname($project_folder)),
                'shellExecutable' => '/bin/bash',
                'executables' => $host_config['executables'] ?? [],
            ], $this->kubectlShell, $context->getConfigurationService());
            $this->kubectlShell->setHostConfig($shell_host_config);
            $this->kubectlShell->applyEnvironment($kube_config['environment']);

            if (!$this->kubectlShell->exists($project_folder)) {
                $this->logger->info('Creating project folder ' . $project_folder);
                $this->kubectlShell->cd($context->getConfigurationService()->getFabfilePath());
                $this->kubectlShell->run(sprintf('mkdir -p %s', $project_folder));
            }
        }

        return $project_folder;
    }

    /**
     * @param HostConfig $host_config
     * @param TaskContextInterface $context
     * @param array $data
     * @param array $vars
     * @return array
     */
    protected function expandReplacements(
        HostConfig $host_config,
        TaskContextInterface $context,
        array $data,
        array $vars = []
    ): array {
        $replacements = Utilities::expandVariables(
            Utilities::mergeData([
                'globals' => Utilities::getGlobalReplacements($context->getConfigurationService()),
                'host' => $host_config->raw(),
                'context' => $context->getData(),
            ], $vars)
        );
        $data = Utilities::expandStrings($data, $replacements);
        return $data;
    }
}
