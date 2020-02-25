<?php

namespace Phabalicious\Method;

use Phabalicious\Configuration\ConfigurationService;
use Phabalicious\Configuration\HostConfig;
use Phabalicious\Exception\EarlyTaskExitException;
use Phabalicious\ShellProvider\ShellProviderInterface;
use Phabalicious\Utilities\Utilities;
use Phabalicious\Validation\ValidationErrorBagInterface;
use Phabalicious\Validation\ValidationService;

class GitMethod extends BaseMethod implements MethodInterface
{

    public function getName(): string
    {
        return 'git';
    }

    public function supports(string $method_name): bool
    {
        return $method_name === 'git';
    }

    public function getGlobalSettings(): array
    {
        return [
            'gitOptions' =>  [
                'pull' => [
                    '--no-edit',
                    '--rebase'
                ],
            ],
            'executables' => [
                'git' => 'git',
            ],
        ];
    }

    public function getDefaultConfig(ConfigurationService $configuration_service, array $host_config): array
    {
        return [
            'branch' => 'develop',
            'gitRootFolder' => $host_config['rootFolder'] ?? null,
            'ignoreSubmodules' => false,
            'gitOptions' => $configuration_service->getSetting('gitOptions', []),
        ];
    }

    public function validateConfig(array $config, ValidationErrorBagInterface $errors)
    {
        $validation = new ValidationService($config, $errors, sprintf('host-config: `%s`', $config['configName']));
        $validation->hasKey('gitRootFolder', 'gitRootFolder should point to your gits root folder.');
        $validation->checkForValidFolderName('gitRootFolder');
        $validation->hasKey('branch', 'git needs a branch-name so it can run deployments.');
    }

    public function alterConfig(ConfigurationService $configuration_service, array &$data)
    {
        parent::alterConfig($configuration_service, $data); // TODO: Change the autogenerated stub
        if (empty($data['deployMethod'])) {
            $data['deployMethod'] = 'git';
        }
    }

    public function getTag(HostConfig $host_config, TaskContextInterface $context)
    {
        $host_config->shell()->pushWorkingDir($host_config['gitRootFolder']);
        $result = $host_config->shell()->run('#!git describe --exact-match', true);
        $host_config->shell()->popWorkingDir();
        return $result->succeeded() ? str_replace('/', '-', $result->getOutput()[0]) : false;
    }
    public function getVersion(HostConfig $host_config, TaskContextInterface $context)
    {
        $host_config->shell()->pushWorkingDir($host_config['gitRootFolder']);
        $result = $host_config->shell()->run('#!git describe --always --tags', true);
        $host_config->shell()->popWorkingDir();
        return $result->succeeded() ? str_replace('/', '-', $result->getOutput()[0]) : '';
    }

    public function getCommitHash(HostConfig $host_config, TaskContextInterface $context)
    {
        $host_config->shell()->pushWorkingDir($host_config['gitRootFolder']);
        $result = $host_config->shell()->run('#!git rev-parse HEAD', true);
        $host_config->shell()->popWorkingDir();
        return $result->getOutput()[0];
    }

    public function isWorkingcopyClean(HostConfig $host_config, TaskContextInterface $context)
    {
        $host_config->shell()->pushWorkingDir($host_config['gitRootFolder']);
        $result = $host_config->shell()->run('#!git diff --exit-code --quiet', true);
        $host_config->shell()->popWorkingDir();
        return $result->succeeded();
    }

    public function version(HostConfig $host_config, TaskContextInterface $context)
    {
        $context->setResult('version', $this->getVersion($host_config, $context));
    }

    /**
     * @param HostConfig $host_config
     * @param TaskContextInterface $context
     * @throws EarlyTaskExitException
     */
    public function deploy(HostConfig $host_config, TaskContextInterface $context)
    {
        if ($host_config['deployMethod'] !== 'git') {
            return ;
        }
        $shell = $this->getShell($host_config, $context);
        $shell->cd($host_config['gitRootFolder']);

        if (!$this->isWorkingcopyClean($host_config, $context)) {
            $this->logger->error('Working copy is not clean, aborting');
            $result = $shell->run('#!git status');
            throw new EarlyTaskExitException();
        }

        $branch = $context->get('branch', $host_config['branch']);

        $shell->run('#!git fetch -q origin');
        $shell->run('#!git checkout ' . $branch);
        $shell->run('#!git fetch --tags');

        $git_options = implode(' ', Utilities::getProperty($host_config, 'gitOptions.pull', []));
        $shell->run('#!git pull -q ' . $git_options . ' origin ' . $branch);

        if (empty($host_config['ignoreSubmodules'])) {
            $shell->run('#!git submodule update --init');
            $shell->run('#!git submodule sync');
        }
    }


    public function backupPrepare(HostConfig $host_config, TaskContextInterface $context)
    {
        $hash = $this->getVersion($host_config, $context);
        if ($hash) {
            $basename = $context->getResult('basename', []);
            array_splice($basename, 1, 0, $hash);
            $context->setResult('basename', $basename);
        }
    }

    public function getMetaInformation(HostConfig $host_config, TaskContextInterface $context)
    {
        $context->addResult('meta', [
            new MetaInformation('Version', $this->getVersion($host_config, $context), true),
            new MetaInformation('Commit', $this->getCommitHash($host_config, $context), true),
        ]);
    }

    public function appCheckExisting(HostConfig $host_config, TaskContextInterface $context)
    {
        if (!$context->getResult('appInstallDir', false)) {
            $context->setResult('appInstallDir', $host_config['gitRootFolder']);
        }
    }

    public function appCreate(HostConfig $host_config, TaskContextInterface $context)
    {
        if (!$current_stage = $context->get('currentStage', false)) {
            throw new \InvalidArgumentException('Missing currentStage on context!');
        }

        if ($current_stage !== 'installCode') {
            return;
        }
        /** @var ShellProviderInterface $shell */
        $shell = $context->get('outerShell', $host_config->shell());
        $install_dir = $context->get('installDir', $host_config['gitRootFolder']);

        $repository = $context->getConfigurationService()->getSetting('repository', false);
        if (!$repository) {
            throw new \InvalidArgumentException('Missing `repository` in fabfile! Cannot proceed!');
        }
        
        $this->ensureKnownHosts(
            $context->getConfigurationService(),
            $this->getKnownHosts($host_config, $context),
            $shell
        );

        $shell->run(sprintf(
            '#!git clone -b %s %s %s',
            $host_config['branch'],
            $repository,
            $install_dir
        ));

        $cwd = $shell->getWorkingDir();

        if (!$host_config['ignoreSubmodules']) {
            $shell->cd($install_dir);
            $shell->run('#!git submodule update --init');
        }

        $shell->run('touch .projectCreated');
        $shell->cd($cwd);
    }

    public function preflightTask(string $task, HostConfig $config, TaskContextInterface $context)
    {
        parent::preflightTask($task, $config, $context); // TODO: Change the autogenerated stub
        if (in_array($task, ['deploy'])) {
            $this->ensureKnownHosts(
                $context->getConfigurationService(),
                $this->getKnownHosts($config, $context),
                $config->shell()
            );
        }
    }
}
