<?php

namespace Phabalicious\Method;

use Phabalicious\Artifact\Actions\ActionFactory;
use Phabalicious\Artifact\Actions\Ftp\ExcludeAction;
use Phabalicious\Configuration\ConfigurationService;
use Phabalicious\Configuration\HostConfig;
use Phabalicious\Exception\MethodNotFoundException;
use Phabalicious\Exception\MissingScriptCallbackImplementation;
use Phabalicious\Exception\TaskNotFoundInMethodException;
use Phabalicious\Utilities\AppDefaultStages;
use Phabalicious\Validation\ValidationErrorBagInterface;
use Phabalicious\Validation\ValidationService;
use Psr\Log\LoggerInterface;

class ArtifactsCustomMethod extends ArtifactsBaseMethod implements MethodInterface
{

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
        ActionFactory::register($this->getName(), 'exclude', ExcludeAction::class);
    }

    public function getName(): string
    {
        return 'artifacts--custom';
    }

    public function supports(string $method_name): bool
    {
        return $method_name === $this->getName();
    }

    public function getDefaultConfig(ConfigurationService $configuration_service, array $host_config): array
    {
        $return = parent::getDefaultConfig($configuration_service, $host_config);
        $return['tmpFolder'] = '/tmp';
        $return['deployMethod'] = $this->getName();
        $return[self::PREFS_KEY] = [
            'useLocalRepository' => false,
        ];

        return $return;
    }

    /**
     * @param array $config
     * @param ValidationErrorBagInterface $errors
     */
    public function validateConfig(array $config, ValidationErrorBagInterface $errors)
    {
        parent::validateConfig($config, $errors);
        $validation = new ValidationService($config[self::PREFS_KEY], $errors, "artifact settings");
        $validation->hasKey('stages', '`stages` is required.');
        $validation->isArray('stages', '`stages` should be an array');
    }

    /**
     * @param HostConfig $host_config
     * @param TaskContextInterface $context
     * @throws MethodNotFoundException
     * @throws MissingScriptCallbackImplementation
     * @throws TaskNotFoundInMethodException
     */
    public function deploy(HostConfig $host_config, TaskContextInterface $context)
    {

        $stages = $host_config[self::PREFS_KEY]['stages'];
        $stages = $this->prepareDirectoriesAndStages($host_config, $context, $stages, true);

        $this->buildArtifact($host_config, $context, $stages);

        $this->cleanupDirectories($host_config, $context);
        // Do not run any next tasks.
        $context->setResult('runNextTasks', []);
    }

    /**
     * @param HostConfig $host_config
     * @param TaskContextInterface $context
     */
    public function appCreate(HostConfig $host_config, TaskContextInterface $context)
    {
        $this->runStageSteps($host_config, $context, []);
    }
}
