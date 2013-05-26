<?php

namespace Sli\ExtJsIntegrationBundle\Tests\DependencyInjection;

use Sli\ExtJsIntegrationBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /* @var Configuration */
    private $c;

    public function setUp()
    {
        $this->c = new Configuration();
    }

    public function testWithProperConfiguration()
    {
        $processor = new Processor();

        foreach (Configuration::$VALID_EXTENSIBILITY_METHODS as $methodName) {
            $processor->processConfiguration($this->c, array(
                'sli_ext_js_integration' => array(
                    'extensibility_method' => $methodName
                )
            ));
        }
    }

    public function testWithInvalidConfiguration()
    {
        $processor = new Processor();

        $thrownException = null;
        try {
            $processor->processConfiguration($this->c, array(
                'sli_ext_js_integration' => array(
                    'extensibility_method' => 'foo'
                )
            ));
        } catch (InvalidConfigurationException $e) {
            $thrownException = $e;
        }

        $this->assertInstanceOf(
            'Symfony\Component\Config\Definition\Exception\InvalidConfigurationException',
            $thrownException
        );
        $this->assertEquals('sli_ext_js_integration/extensibility_mechanism', $thrownException->getPath());
    }
}
