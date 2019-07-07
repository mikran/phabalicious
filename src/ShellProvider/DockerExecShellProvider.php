<?php

namespace Phabalicious\ShellProvider;

use Phabalicious\Configuration\ConfigurationService;
use Phabalicious\Method\DockerMethod;
use Phabalicious\Method\TaskContextInterface;
use Phabalicious\Validation\ValidationErrorBagInterface;
use Phabalicious\Validation\ValidationService;

class DockerExecShellProvider extends LocalShellProvider implements ShellProviderInterface
{
    const PROVIDER_NAME = 'docker-exec';

    public function getDefaultConfig(ConfigurationService $configuration_service, array $host_config): array
    {
        $result =  parent::getDefaultConfig($configuration_service, $host_config);
        $result['shellProviderExecutable'] = 'docker';
        $result['shellExecutable'] = '/bin/bash';

        return $result;
    }

    public function validateConfig(array $config, ValidationErrorBagInterface $errors)
    {
        parent::validateConfig($config, $errors);

        $validation = new ValidationService($config, $errors, 'host-config');
        $validation->hasKeys([
            'docker' => 'The docker-configuration to use',
        ]);
        if (!$errors->hasErrors()) {
            $validation = new ValidationService($config['docker'], $errors, 'host:docker');
            if (empty($config['docker']['service'])) {
                $validation->hasKey('name', 'The name of the docker-container to use');
            } else {
                $validation->hasKey('service', 'The service of the docker-compose to use');
            }
        }
    }


    public function getShellCommand(array $options = []): array
    {
        $command = [
            $this->hostConfig['shellProviderExecutable'],
            'exec',
            (empty($options['tty']) ? '-i' : '-it'),
            $this->hostConfig['docker']['name'],
        ];
        if (!empty($options['tty']) && empty($options['shell_provided'])) {
            $command[] = $this->hostConfig['shellExecutable'];
        }


        return $command;
    }

    /**
     * @param string $dir
     * @return bool
     * @throws \Exception
     */
    public function exists($dir):bool
    {
        $result = $this->run(sprintf('stat %s > /dev/null 2>&1', $dir), false, false);
        return $result->succeeded();
    }

    public function putFile(string $source, string $dest, TaskContextInterface $context, bool $verbose = false): bool
    {
        $command = [
            'docker',
            'cp',
            $source,
            $this->hostConfig['docker']['name'] . ':' . $dest
        ];

        return $this->runProcess($command, $context, false, true);
    }

    public function getFile(string $source, string $dest, TaskContextInterface $context, bool $verbose = false): bool
    {
        $command = [
            'docker',
            'cp',
            $this->hostConfig['docker']['name'] . ':' . $source,
            $dest,
        ];

        return $this->runProcess($command, $context, false, true);
    }


    /**
     * {@inheritdoc}
     */
    public function wrapCommandInLoginShell(array $command)
    {
        array_unshift(
            $command,
            '/bin/bash',
            '--login',
            '-c'
        );
        return $command;
    }
}
