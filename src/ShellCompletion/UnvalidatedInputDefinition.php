<?php

namespace Phabalicious\ShellCompletion;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class UnvalidatedInputDefinition
 *
 * This class mocks a valid INputDefinition and creates missing options and arguments
 * on the fly.
 *
 * @package Phabalicious\ShellCompletion
 */
class UnvalidatedInputDefinition extends InputDefinition
{
    public function hasShortcut($name)
    {
        return true;
    }

    public function hasOption($name)
    {
        return true;
    }

    public function hasArgument($name)
    {
        return true;
    }

    public function getArgument($name)
    {
        if (!parent::hasArgument($name)) {
            $this->addArgument(new InputArgument(
                $name,
                InputArgument::OPTIONAL
            ));
        }
        return parent::getArgument($name); // TODO: Change the autogenerated stub
    }

    public function getOption($name)
    {
        if (!parent::hasOption($name)) {
            $this->AddOption(new InputOption(
                $name,
                InputOption::VALUE_OPTIONAL
            ));
        }
        return parent::getOption($name); // TODO: Change the autogenerated stub
    }

}