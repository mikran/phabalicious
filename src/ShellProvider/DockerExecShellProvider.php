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
        $result = parent::getDefaultConfig($configuration_service, $host_config);
        $result['dockerExecutable'] = 'docker';
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


    public function getShellCommand(array $program_to_call, ShellOptions $options): array
    {
        $command = [
            $this->hostConfig['dockerExecutable'],
            'exec',
            ($options->useTty() ? '-it' : '-i'),
            $this->hostConfig['docker']['name'],
        ];
        if ($options->useTty() && !$options->isShellExecutableProvided()) {
            $command[] = $this->hostConfig['shellExecutable'];
        }

        if (count($program_to_call)) {
            foreach ($program_to_call as $e) {
                $command[] = $e;
            }
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
        $command = $this->getPutFileCommand($source, $dest);
        return $this->runProcess($command, $context, false, true);
    }

    public function getFile(string $source, string $dest, TaskContextInterface $context, bool $verbose = false): bool
    {
        $command = $this->getGetFileCommand($source, $dest);
        return $this->runProcess($command, $context, false, true);
    }


    /**
     * {@inheritdoc}
     */
    public function wrapCommandInLoginShell(array $command)
    {
        return array_merge([
            '/bin/bash',
            '--login',
            '-c',
        ], $command);
    }

    /**
     * @param string $source
     * @param string $dest
     * @return string[]
     */
    public function getPutFileCommand(string $source, string $dest): array
    {
        return [
            'docker',
            'cp',
            $source,
            $this->hostConfig['docker']['name'] . ':' . $dest
        ];
    }

    /**
     * @param string $source
     * @param string $dest
     * @return string[]
     */
    public function getGetFileCommand(string $source, string $dest): array
    {
        return [
            'docker',
            'cp',
            $this->hostConfig['docker']['name'] . ':' . $source,
            $dest,
        ];
    }
}
