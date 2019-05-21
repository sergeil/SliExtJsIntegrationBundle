<?php

namespace Sli\ExtJsIntegrationBundle\Tests\Rendering;

use Sli\ExtJsIntegrationBundle\Rendering\BundleRenderersProvider;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Sli\ExtJsIntegrationBundle\Rendering\RenderersAwareBundle;

class DummyTestBundle extends Bundle implements RenderersAwareBundle
{
    private $renderers;

    /**
     * @return array
     */
    public function getRenderers()
    {
        return $this->renderers;
    }

    public function __construct(array $renderers)
    {
        $this->renderers = $renderers;
    }
}

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class BundleRenderersProviderTest extends \PHPUnit_Framework_TestCase
{
    /* @var BundleRenderersProvider */
    private $provider;

    public function setUp()
    {
        $bundles = array(
            new DummyTestBundle(array(
                'first_renderer' => function($value) {
                    return 'foo-'.$value;
                }
            )),
            new DummyTestBundle(array(
                'second_renderer' => function($value) {
                    return 'bar-'.$value;
                }
            ))
        );

        $kernel = $this->createMock('Symfony\Component\HttpKernel\Kernel', array(), array(), '', false);
        $kernel->expects($this->once())
               ->method('getBundles')
               ->will($this->returnValue($bundles));


        $this->provider = new BundleRenderersProvider($kernel);
    }

    public function testGet()
    {
        $renderer = $this->provider->get('first_renderer');
        $this->assertEquals('foo-opa', $renderer('opa'));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetNotExistingRenderer()
    {
        $this->provider->get('foo');
    }

    public function testHas()
    {
        $this->assertTrue($this->provider->has('first_renderer'));
        $this->assertTrue($this->provider->has('second_renderer'));
        $this->assertFalse($this->provider->has('blah_renderer'));
    }
}
