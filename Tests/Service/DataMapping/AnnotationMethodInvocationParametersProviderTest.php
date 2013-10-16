<?php

namespace Sli\ExtJsIntegrationBundle\Tests\Service\DataMapping;

use Sli\ExtJsIntegrationBundle\Service\DataMapping\AnnotationMethodInvocationParametersProvider as Provider;
use Sli\ExtJsIntegrationBundle\Service\DataMapping\Params as Params;
use Symfony\Component\DependencyInjection\Container;

class FooEntity
{
    /**
     * @Params({"foo", "bar"})
     */
    public function foo($fooService, $barService)
    {

    }

    /**
     * @Params({"baz-service*"})
     */
    public function baz($bazService)
    {

    }
}

class MockContainer extends Container
{
    public function get($id, $invalidBehavior = Container::EXCEPTION_ON_INVALID_REFERENCE)
    {
        if ('baz-service' == $id && Container::EXCEPTION_ON_INVALID_REFERENCE == $invalidBehavior) {
            throw new \RuntimeException(
                'When a service-name is marked with "*", then the invalidBahaviour must be NULL_ON_INVALID_REFERENCE'
            );
        }
        return "$id-service-instance";
    }
}

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class AnnotationMethodInvocationParametersProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetParameters()
    {
        $c = new MockContainer();

        $p = new Provider($c);
        $params = $p->getParameters(__NAMESPACE__.'\\FooEntity', 'foo');
        $this->assertTrue(is_array($params));
        $this->assertEquals(2, count($params));
        $this->assertSame('foo-service-instance', $params[0]);
        $this->assertSame('bar-service-instance', $params[1]);

        $params = $p->getParameters(__NAMESPACE__.'\\FooEntity', 'baz');
    }
}
