<?php

namespace Phabalicious\Tests;

use Phabalicious\Configuration\ConfigurationService;
use Phabalicious\Method\BaseMethod;
use Phabalicious\Method\MethodFactory;
use Psr\Log\AbstractLogger;
use Symfony\Component\Console\Application;

class MethodFactoryTest extends PhabTestCase
{

    /**
     * @var MethodFactory
     */
    private $methods;

    public function setUp()
    {
        parent::setUp(); // TODO: Change the autogenerated stub

        $application = $this->getMockBuilder(Application::class)->getMock();
        $logger = $this->getMockBuilder(AbstractLogger::class)->getMock();
        $config_factory = $this->getMockBuilder(ConfigurationService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->methods = new MethodFactory($config_factory, $logger);
    }

    public function testGetMethod()
    {
        $method = $this->getMockBuilder(BaseMethod::class)
            ->setMethods(['getName', 'supports'])
            ->disableOriginalConstructor()
            ->getMock();
        $method->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('mocked_method'));
        $method->expects($this->any())
            ->method('supports')
            ->will($this->returnValue(true));

        $this->methods->addMethod($method);

        $found_method = $this->methods->getMethod($method->getName());


        $this->assertEquals($found_method->getName(), $method->getName());
    }
}
