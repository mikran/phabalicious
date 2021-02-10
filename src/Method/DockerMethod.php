<?php /** @noinspection PhpUnusedLocalVariableInspection */

namespace Phabalicious\Method;

use Phabalicious\Configuration\ConfigurationService;
use Phabalicious\Configuration\DockerConfig;
use Phabalicious\Configuration\HostConfig;
use Phabalicious\Exception\FailedShellCommandException;
use Phabalicious\Exception\MethodNotFoundException;
use Phabalicious\Exception\MismatchedVersionException;
use Phabalicious\Exception\MissingDockerHostConfigException;
use Phabalicious\Exception\MissingScriptCallbackImplementation;
use Phabalicious\Exception\ValidationFailedException;
use Phabalicious\ScopedLogLevel\ScopedErrorLogLevel;
use Phabalicious\ScopedLogLevel\ScopedLogLevel;
use Phabalicious\ShellProvider\CommandResult;
use Phabalicious\ShellProvider\ShellOptions;
use Phabalicious\ShellProvider\ShellProviderInterface;
use Phabalicious\Utilities\Utilities;
use Phabalicious\Validation\ValidationErrorBagInterface;
use Phabalicious\Validation\ValidationService;
use Psr\Log\LogLevel;

class DockerMethod extends BaseMethod implements MethodInterface
{

    protected $cache = [];

    public function getName(): string
    {
        return 'docker';
    }

    public function supports(string $method_name): bool
    {
        return $method_name === 'docker';
    }

    public function getDefaultConfig(ConfigurationService $configuration_service, array $host_config): array
    {
        $config = parent::getDefaultConfig($configuration_service, $host_config); // TODO: Change the autogenerated stub
        $config['executables']['supervisorctl'] = 'supervisorctl';
        $config['executables']['docker-compose'] = 'docker-compose';
        $config['executables']['docker'] = 'docker';
        $config['executables']['chmod'] = 'chmod';
        $config['executables']['chown'] = 'chown';
        $config['executables']['ssh-add'] = 'ssh-add';

        if (!empty($host_config['sshTunnel']) &&
            !empty($host_config['docker']['name']) &&
            empty($host_config['sshTunnel']['destHostFromDockerContainer']) &&
            empty($host_config['sshTunnel']['destHost'])
        ) {
            $config['sshTunnel']['destHostFromDockerContainer'] = $host_config['docker']['name'];
        }
        return $config;
    }

    public function validateConfig(array $config, ValidationErrorBagInterface $errors)
    {
        parent::validateConfig($config, $errors); // TODO: Change the autogenerated stub
        $validation = new ValidationService($config, $errors, sprintf('host: `%s`', $config['configName']));
        $validation->isArray('docker', 'docker configuration needs to be an array');
        if (!$errors->hasErrors()) {
            $validation = new ValidationService(
                $config['docker'],
                $errors,
                sprintf('host.docker: `%s`', $config['configName'])
            );
            if (empty($config['docker']['service'])) {
                $validation->hasKey('name', 'name of the docker-container to inspect');
            }

            $validation->hasKey(
                'projectFolder',
                'projectFolder where the project is stored, relative to the rootFolder'
            );
            $validation->checkForValidFolderName('projectFolder');
            $validation->hasKey('configuration', 'name of the docker-configuration to use');
        }
    }

    public function alterConfig(ConfigurationService $configuration_service, array &$data)
    {
        if (!empty($data['docker']['service'])) {
            unset($data['docker']['name']);
        }
    }

    public function isRunningAppRequired(HostConfig $host_config, TaskContextInterface $context, string $task): bool
    {
        return in_array($task, ['startRemoteAccess']);
    }

    /**
     * @param HostConfig $host_config
     * @param ConfigurationService $config
     * @return DockerConfig
     * @throws MismatchedVersionException
     * @throws MissingDockerHostConfigException
     * @throws ValidationFailedException
     */
    public static function getDockerConfig(HostConfig $host_config, ConfigurationService $config)
    {
        $config = $config->getDockerConfig($host_config['docker']['configuration']);
        $config['executables'] = $host_config['executables'];
        return $config;
    }

    /**
     * @param HostConfig $host_config
     * @param TaskContextInterface $context
     * @throws FailedShellCommandException
     * @throws MethodNotFoundException
     * @throws MismatchedVersionException
     * @throws MissingDockerHostConfigException
     * @throws MissingScriptCallbackImplementation
     * @throws ValidationFailedException
     */
    public function docker(HostConfig $host_config, TaskContextInterface $context)
    {
        $task = $context->get('docker_task');

        $this->runTaskImpl($host_config, $context, $task . 'Prepare', true);
        $this->runTaskImpl($host_config, $context, $task, false);
        $this->runTaskImpl($host_config, $context, $task . 'Finished', true);

        $context->io()->success(sprintf('Task `%s` executed successfully!', $task));
    }

    /**
     * @param HostConfig $host_config
     * @param TaskContextInterface $context
     * @param string $task
     * @param bool $silent
     * @throws MethodNotFoundException
     * @throws MismatchedVersionException
     * @throws MissingDockerHostConfigException
     * @throws MissingScriptCallbackImplementation
     * @throws ValidationFailedException
     * @throws FailedShellCommandException
     */
    private function runTaskImpl(HostConfig $host_config, TaskContextInterface $context, $task, $silent)
    {
        $this->logger->info('Running docker-task `' . $task . '` on `' . $host_config['configName']);

        if (method_exists($this, $task)) {
            $this->{$task}($host_config, $context);
            return;
        }

        $docker_config = $this->getDockerConfig($host_config, $context->getConfigurationService());
        $tasks = $docker_config['tasks'];

        if ($silent && empty($tasks[$task])) {
            return;
        }
        if (empty($tasks[$task])) {
            throw new MethodNotFoundException('Missing task `' . $task . '`');
        }

        $script = $tasks[$task];
        $environment = $docker_config->get('environment', []);
        $callbacks = [];

        /** @var ScriptMethod $method */
        $method = $context->getConfigurationService()->getMethodFactory()->getMethod('script');
        $context->set(ScriptMethod::SCRIPT_DATA, $script);
        $context->mergeAndSet('variables', [
            'dockerHost' => $docker_config->raw(),
        ]);
        $context->set('environment', $environment);
        $context->set('callbacks', $callbacks);
        $context->set('rootFolder', self::getProjectFolder($docker_config, $host_config));
        $context->setShell($docker_config->shell());
        $docker_config->shell()->setOutput($context->getOutput());

        $method->runScript($host_config, $context);

        /** @var CommandResult $cr */
        $cr = $context->getResult('commandResult', false);
        if ($cr && $cr->failed()) {
            $cr->throwException(sprintf('Docker task `%s` failed!', $task));
        }
    }

    public function getInternalTasks(): array
    {
        return [
            'waitForServices',
            'copySSHKeys',
            'startRemoteAccess'
        ];
    }

    /**
     * @param HostConfig $hostconfig
     * @param TaskContextInterface $context
     * @throws ValidationFailedException
     * @throws MismatchedVersionException
     * @throws MissingDockerHostConfigException
     */
    public function waitForServices(HostConfig $hostconfig, TaskContextInterface $context)
    {
        if ($hostconfig['executables']['supervisorctl'] === false) {
            return;
        }
        $max_tries = 10;
        $tries = 0;
        $docker_config = $this->getDockerConfig($hostconfig, $context->getConfigurationService());
        $container_name = $this->getDockerContainerName($hostconfig, $context->getConfigurationService());
        $shell = $docker_config->shell();

        if (!$this->isContainerRunning($docker_config, $container_name)) {
            throw new \RuntimeException(sprintf(
                'Docker container %s is not running or could not be discovered! Check your docker config!',
                $container_name
            ));
        }

        while ($tries < $max_tries) {
            $error_log_level = new ScopedErrorLogLevel($shell, LogLevel::NOTICE);
            $result = $shell->run(sprintf('#!docker exec %s #!supervisorctl status', $container_name), true, false);
            $error_log_level = null;

            $count_running = 0;
            $count_services = 0;
            foreach ($result->getOutput() as $line) {
                if (trim($line) != '') {
                    $count_services++;
                    if (strpos($line, 'RUNNING')) {
                        $count_running++;
                    }
                }
            }
            if ($result->getExitCode() !== 0) {
                $this->logger->notice('Error running supervisorctl, check the logs');
            }
            if ($result->getExitCode() == 0 && ($count_running == $count_services)) {
                $context->io()->comment('Services up and running!');
                return;
            }
            $tries++;
            $this->logger->notice(sprintf(
                'Waiting for 5 secs and try again (%d/%d)...',
                $tries,
                $max_tries
            ));
            sleep(5);
        }
        $this->logger->error('Supervisord not coming up at all!');
    }

    public static function getProjectFolder(DockerConfig $docker_config, HostConfig $host_config)
    {
        return $docker_config['rootFolder'] . '/'. $host_config['docker']['projectFolder'];
    }

    /**
     * @param HostConfig $hostconfig
     * @param TaskContextInterface $context
     *
     * @throws ValidationFailedException
     * @throws MismatchedVersionException
     * @throws MissingDockerHostConfigException|\Phabalicious\Exception\FabfileNotReadableException
     */
    private function copySSHKeys(HostConfig $hostconfig, TaskContextInterface $context)
    {
        $files = [];
        $temp_files = [];
        $temp_nam_prefix = 'phab-' . md5($hostconfig['configName'] . mt_rand());


        // Backwards-compatibility:
        if ($file = $context->getConfigurationService()->getSetting('dockerAuthorizedKeyFile')) {
            $files['/root/.ssh/authorized_keys'] = [
                'source' => $file,
                'permissions' => '600',
            ];
        }
        if ($file = $context->getConfigurationService()->getSetting('dockerAuthorizedKeysFile')) {
            $files['/root/.ssh/authorized_keys'] = [
                'source' => $file,
                'permissions' => '600',
            ];
        }
        if ($file = $context->getConfigurationService()->getSetting('dockerKeyFile')) {
            $files['/root/.ssh/id_rsa'] = [
                'source' => $file,
                'permissions' => '600',
            ];
            $files['/root/.ssh/id_rsa.pub'] = [
                'source' => $file . '.pub',
                'permissions' => '644',
            ];
        }

        if ($file = $context->getConfigurationService()->getSetting('dockerKnownHostsFile')) {
            $files['/root/.ssh/known_hosts'] = [
                'source' => $file,
                'permissions' => '600',
            ];
        }

        if ($file = $context->getConfigurationService()->getSetting('dockerNetRcFile')) {
            $files['/root/.netrc'] = [
                'source' => $file,
                'permissions' => '600',
                'optional' => true,
            ];
        }

        $docker_config = $this->getDockerConfig($hostconfig, $context->getConfigurationService());
        $root_folder = $this->getProjectFolder($docker_config, $hostconfig);

        $shell = $docker_config->shell();

        // If no authorized_keys file is set, then add all public keys from the agent into the container.
        if (empty($files['/root/.ssh/authorized_keys'])) {
            $file = tempnam("/tmp", $temp_nam_prefix);

            try {
                $result = $shell->run(sprintf('#!ssh-add -L > %s', $file));

                $files['/root/.ssh/authorized_keys'] = [
                    'source' => $file,
                    'permissions' => '600',
                ];
                $temp_files[] = $file;
            } catch (FailedShellCommandException $e) {
                $context->io()->warning(sprintf(
                    'Could not add public key to authorized_keys-file: %s',
                    $e->getMessage()
                ));
            }
        }

        if (count($files) > 0) {
            $container_name = $this->getDockerContainerName($hostconfig, $context->getConfigurationService());
            if (!$this->isContainerRunning($docker_config, $container_name)) {
                throw new \RuntimeException(sprintf(
                    'Docker container %s not running, check your `host.docker.name` configuration!',
                    $container_name
                ));
            }
            $shell->run(sprintf('#!docker exec %s mkdir -p /root/.ssh', $container_name));

            foreach ($files as $dest => $data) {
                if ((substr($data['source'], 0, 7) == 'http://') ||
                    (substr($data['source'], 0, 8) == 'https://')) {
                    $content = $context->getConfigurationService()->readHttpResource($data['source']);
                    $temp_file = tempnam("/tmp", $temp_nam_prefix);
                    file_put_contents($temp_file, $content);
                    $data['source'] = $temp_file;
                    $temp_files[] = $temp_file;
                } elseif ($data['source'][0] !== '/') {
                    $data['source'] =
                          $context->getConfigurationService()->getFabfilePath() .
                          '/' .
                          $data['source'];
                }

                // Check if file exists
                if (!file_exists($data['source'])) {
                    if (empty($data['optional'])) {
                        throw new \RuntimeException(sprintf(
                            'File `%s does not exist, could not copy into container!',
                            $data['source']
                        ));
                    } else {
                        $context->io()->comment(sprintf('File `%s does not exist, skipping!', $data['source']));
                        continue;
                    }
                }

                $temp_file = $docker_config['tmpFolder'] . '/' . $temp_nam_prefix . '-' . basename($data['source']);
                $shell->putFile($data['source'], $temp_file, $context);

                $shell->run(sprintf('#!docker cp %s %s:%s', $temp_file, $container_name, $dest));
                $shell->run(sprintf('#!docker exec %s #!chmod %s %s', $container_name, $data['permissions'], $dest));
                $shell->run(sprintf('rm %s', $temp_file));
                $context->io()->comment(sprintf('Handled %s successfully!', $dest));
            }
            $shell->run(sprintf('#!docker exec %s #!chmod 700 /root/.ssh', $container_name));
            $shell->run(sprintf('#!docker exec %s #!chown -R root /root/.ssh', $container_name));
        }

        foreach ($temp_files as $temp_file) {
            @unlink($temp_file);
        }
    }

    public function isContainerRunning(HostConfig $docker_config, $container_name): bool
    {
        $shell = $docker_config->shell();
        $scoped_loglevel = new ScopedLogLevel($shell, LogLevel::DEBUG);
        $result = $shell->run(sprintf(
            '#!docker inspect -f {{.State.Running}} %s',
            $container_name
        ), true);

        $output = $result->getOutput();
        $last_line = array_pop($output);
        if (strtolower(trim($last_line)) !== 'true') {
            return false;
        }

        return true;
    }

    /**
     * @param HostConfig $host_config
     * @param TaskContextInterface $context
     * @return bool|string
     * @throws ValidationFailedException
     * @throws MismatchedVersionException
     * @throws MissingDockerHostConfigException
     */
    public function getIpAddress(HostConfig $host_config, TaskContextInterface $context)
    {
        if (!empty($this->cache[$host_config['configName']])) {
            return $this->cache[$host_config['configName']];
        }
        $docker_config = $this->getDockerConfig($host_config, $context->getConfigurationService());
        $shell = $docker_config->shell();
        $scoped_loglevel = new ScopedLogLevel($shell, LogLevel::DEBUG);
        try {
            $container_name = $this->getDockerContainerName($host_config, $context->getConfigurationService());
        } catch (\RuntimeException $e) {
            return false;
        }

        if (!$this->isContainerRunning($docker_config, $container_name)) {
            return false;
        }

        $result = $shell->run(sprintf(
            '#!docker inspect --format "{{range .NetworkSettings.Networks}}{{.IPAddress}}|{{end}}" %s',
            $container_name
        ), true);

        if ($result->getExitCode() === 0) {
            $ips = explode('|', $result->getOutput()[0]);
            $ips = array_filter($ips);
            $ip = reset($ips);
            $this->cache[$host_config['configName']] = $ip;
            return $ip;
        }
        return false;
    }

    /**
     * @param HostConfig $host_config
     * @param TaskContextInterface $context
     * @throws ValidationFailedException
     * @throws MismatchedVersionException
     * @throws MissingDockerHostConfigException
     */
    public function startRemoteAccess(HostConfig $host_config, TaskContextInterface $context)
    {
        $docker_config = $this->getDockerConfig($host_config, $context->getConfigurationService());
        $this->getIp($host_config, $context);
        if (is_a($docker_config->shell(), 'SshShellProvider')) {
            $context->setResult('config', $docker_config);
        }
    }

    /**
     * @param HostConfig $host_config
     * @param TaskContextInterface $context
     * @throws ValidationFailedException
     * @throws MismatchedVersionException
     * @throws MissingDockerHostConfigException
     */
    public function getIp(HostConfig $host_config, TaskContextInterface $context)
    {
        $context->setResult('ip', $this->getIpAddress($host_config, $context));
    }


    /**
     * @param HostConfig $host_config
     * @param TaskContextInterface $context
     * @throws ValidationFailedException
     * @throws MismatchedVersionException
     * @throws MissingDockerHostConfigException
     */
    public function appCheckExisting(HostConfig $host_config, TaskContextInterface $context)
    {
        // Set outer-shell to the one provided by the docker-configuration.
        $docker_config = $this->getDockerConfig($host_config, $context->getConfigurationService());
        $context->setResult('outerShell', $docker_config->shell());
        $context->setResult('installDir', $this->getProjectFolder($docker_config, $host_config));
    }

    /**
     * @param HostConfig $host_config
     * @param TaskContextInterface $context
     * @throws FailedShellCommandException
     * @throws MethodNotFoundException
     * @throws MismatchedVersionException
     * @throws MissingDockerHostConfigException
     * @throws MissingScriptCallbackImplementation
     * @throws ValidationFailedException
     */
    public function appCreate(HostConfig $host_config, TaskContextInterface $context)
    {
        $this->runAppSpecificTask($host_config, $context);
    }

    /**
     * @param HostConfig $host_config
     * @param TaskContextInterface $context
     * @throws FailedShellCommandException
     * @throws MethodNotFoundException
     * @throws MismatchedVersionException
     * @throws MissingDockerHostConfigException
     * @throws MissingScriptCallbackImplementation
     * @throws ValidationFailedException
     */
    public function appDestroy(HostConfig $host_config, TaskContextInterface $context)
    {
        $this->runAppSpecificTask($host_config, $context);
    }

    /**
     * @param HostConfig $host_config
     * @param TaskContextInterface $context
     * @throws FailedShellCommandException
     * @throws MethodNotFoundException
     * @throws MismatchedVersionException
     * @throws MissingDockerHostConfigException
     * @throws MissingScriptCallbackImplementation
     * @throws ValidationFailedException
     */
    public function runAppSpecificTask(HostConfig $host_config, TaskContextInterface $context)
    {
        if (!$current_stage = $context->get('currentStage', false)) {
            throw new \InvalidArgumentException('Missing currentStage on context!');
        }

        $docker_config = $this->getDockerConfig($host_config, $context->getConfigurationService());
        $shell = $docker_config->shell();

        if (isset($docker_config['tasks'][$current_stage]) ||
            in_array($current_stage, array('spinUp', 'spinDown', 'deleteContainer'))
        ) {
            $this->runTaskImpl($host_config, $context, $current_stage, false);
        }
    }

    /**
     * @param HostConfig $host_config
     * @param ConfigurationService $config
     * @return string
     * @throws MismatchedVersionException
     * @throws MissingDockerHostConfigException
     * @throws ValidationFailedException
     */
    public static function getDockerContainerName(HostConfig $host_config, ConfigurationService $config)
    {
        if (!empty($host_config['docker']['name'])) {
            return $host_config['docker']['name'];
        }
        if ($composer_service = $host_config['docker']['service']) {
            $docker_config = self::getDockerConfig($host_config, $config);
            $shell = $docker_config->shell();
            $cwd = $shell->getWorkingDir();
            $shell->cd(self::getProjectFolder($docker_config, $host_config));
            $result = $shell->run(sprintf('#!docker-compose ps -q %s', $composer_service), true);
            $shell->cd($cwd);
            $docker_name = false;
            if ($result->succeeded()) {
                $docker_name = $result->getOutput()[0] ?? false;
            }
            if ($docker_name) {
                $cfg = $host_config['docker'];
                $cfg['name'] = $docker_name;
                $host_config['docker'] = $cfg;

                return $docker_name;
            }

            throw new \RuntimeException(sprintf(
                'Could not get the name of the docker container running the service `%s`',
                $composer_service
            ));
        }
    }

    public function preflightTask(string $task, HostConfig $host_config, TaskContextInterface $context)
    {
        parent::preflightTask($task, $host_config, $context);

        $needs_running_container = $context->getConfigurationService()->isRunningAppRequired(
            $host_config,
            $context,
            $task
        );

        if ($needs_running_container && empty($host_config['docker']['name'])) {
            $this->logger->info('Try to get docker container name ...');
            try {
                $config = $host_config['docker'];
                $host_config->setChild('docker', 'nameAutoDiscovered', true);
                $host_config->setChild(
                    'docker',
                    'name',
                    self::getDockerContainerName(
                        $host_config,
                        $context->getConfigurationService()
                    )
                );
            } catch (\Exception $e) {
            }
        }
        if ($needs_running_container) {
            $ip = $this->getIpAddress($host_config, $context);
            if (!$ip) {
                throw new \RuntimeException('Container is not available, please check your app-runtime!');
            }
        }
    }

    public function postflightTask(string $task, HostConfig $host_config, TaskContextInterface $context)
    {
        parent::postflightTask($task, $host_config, $context);

        // Reset any cached docker container name after a docker task.
        if ($task == 'docker' && !empty($host_config['docker']['nameAutoDiscovered'])) {
            $host_config->setChild('docker', 'name', null);
            $host_config->setChild('docker', 'nameAutoDiscovered', null);
        }
    }

    public function dockerCompose(HostConfig $host_config, TaskContextInterface $context)
    {
        $docker_config = self::getDockerConfig($host_config, $context->getConfigurationService());
        $shell = $docker_config->shell();

        $arguments = $context->get('command', false);
        if (!$arguments) {
            throw new \InvalidArgumentException('Missing command arguments for dockerCompose');
        }

        $variables = Utilities::buildVariablesFrom($host_config, $context);
        $replacements = Utilities::expandVariables($variables);
        $environment = Utilities::expandStrings($docker_config->get('environment', []), $replacements);

        $context->setResult('shell', $shell);

        $command_parts = [
            sprintf('cd %s', self::getProjectFolder($docker_config, $host_config)),
        ];
        foreach ($environment as $k => $v) {
            $command_parts[] = sprintf(" export %s=%s", $k, escapeshellarg($v));
        }
        $command_parts[] = sprintf('#!docker-compose %s', $arguments);

        $command = implode('&&', $command_parts);
        $command = $shell->expandCommand($command);
        $context->setResult('command', [
            $command
        ]);
    }
}
