<?php

namespace Phabalicious\Method;

use Monolog\Handler\Curl\Util;
use Phabalicious\Configuration\ConfigurationService;
use Phabalicious\Configuration\HostConfig;
use Phabalicious\Exception\EarlyTaskExitException;
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

    /** @var ShellProviderInterface */
    protected $shell = null;

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
        $default['kube'] = Utilities::mergeData($global_settings, [
            'scaffoldBeforeDeploy' => true,
            'projectFolder' => 'kube',
            'deployCommand' => 'apply -k .',
            'scaffolder' => [
                'template' => 'simple/index.yml',
            ],
            'parameters' => [
                'namespace' => 'default',
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
            $service->hasKey('deployCommand', 'Provide a deployCommand which gets executed on deploy.');
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
    
    public function deployPrepare(HostConfig $host_config, TaskContextInterface $context)
    {
        $kube_config = $host_config['kube'];
        if (empty($kube_config['scaffolder'])) {
            return;
        }
        if ($kube_config['scaffoldBeforeDeploy']) {
            $this->scaffold($host_config, $context);
        }
        $this->runKubeCtl($host_config, $context, $kube_config['deployCommand']);
        
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
        $kube_config['parameters']['projectFolder'] = $kube_config['projectFolder'];

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

    private function runKubeCtl(HostConfig $host_config, TaskContextInterface $context, $command)
    {
        $kube_config = $host_config['kube'];

        if (!$this->shell) {
            $this->shell = new LocalShellProvider($this->logger);
            $this->shell->setHostConfig($host_config);
        }
        $project_folder = $context->getConfigurationService()->getFabfilePath() . '/' . $kube_config['projectFolder'];
        $this->shell->pushWorkingDir($project_folder);
        $this->shell->run(sprintf('#!kubectl %s', $command));
        $this->shell->popWorkingDir();
    }
}
