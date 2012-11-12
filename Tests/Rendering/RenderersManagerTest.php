<?php

namespace Sli\ExtJsIntegrationBundle\Tests\Rendering;

use Sli\ExtJsIntegrationBundle\Rendering\RenderersManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class RenderersManagerTest extends \PHPUnit_Framework_TestCase
{
    /* @var \Sli\ExtJsIntegrationBundle\Rendering\RenderersManager */
    private $rm;

    public $mockProvider;
    public $mockContainer;

    public function setUp()
    {
        $this->mockProvider = $this->getMock('Sli\ExtJsIntegrationBundle\Rendering\RenderersProviderInterface');
        $this->mockContainer = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $this->rm = new RenderersManager(
            $this->mockProvider,
            $this->mockContainer
        );
    }

    public function testRender()
    {
        $me = $this;
        $closure = function($value, $fieldName, $object, $container) use($me) {
            $me->assertEquals('fooValue', $value);
            $me->assertEquals('fooFieldName', $fieldName);
            $me->assertEquals('fooObject', $object);
            $me->assertSame($me->mockContainer, $container);

            return 'blahResult';
        };
        $this->mockProvider->expects($this->once())
            ->method('get')
            ->with($this->equalTo('blahId'))
            ->will($this->returnValue($closure));

        $this->assertEquals('blahResult', $this->rm->render('blahId', 'fooValue', 'fooFieldName', 'fooObject'));

    }

    private function teachMockProvider($closure)
    {
        $this->mockProvider->expects($this->once())
            ->method('get')
            ->with($this->equalTo('blahId'))
            ->will($this->returnValue($closure));

        $this->mockProvider->expects($this->once())
            ->method('has')
            ->with('blahId')
            ->will($this->returnValue(true));
    }

    public function testRenderSafely()
    {
        $me = $this;
        $closure = function($value, $fieldName, $object) use($me) {
            $me->assertEquals('fooValue', $value);
            $me->assertEquals('fooFieldName', $fieldName);
            $me->assertEquals('fooObject', $object);

            return 'blahResult';
        };
        $this->teachMockProvider($closure);


        $this->assertEquals('blahResult', $this->rm->renderSafely('blahId', 'fooValue', 'fooFieldName', 'fooObject'));
    }

    public function testRenderSafelyWithNotExistingRenderer()
    {
        $this->mockProvider->expects($this->once())
            ->method('has')
            ->will($this->returnValue(false));

        $this->assertEquals("[renderer 'barbaz' is not found]", $this->rm->renderSafely('barbaz', 'fooValue'));
    }

    public function testRenderSafelyWhenExceptionIsThrown()
    {
        $closure = function() {
            throw new \Exception('boom');
        };
        $this->teachMockProvider($closure);

        $this->assertEquals('blahId error: boom', $this->rm->renderSafely('blahId', 'fooValue', 'fooField', 'object'));
    }
}
