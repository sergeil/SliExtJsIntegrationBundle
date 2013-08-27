<?php

namespace Sli\ExtJsIntegrationBundle\Tests\Service;

require_once __DIR__.'/../../Service/SortingFieldAnnotations.php';

use Sli\ExtJsIntegrationBundle\Service\AnnotationSortingFieldResolver;
use Sli\ExtJsIntegrationBundle\Service\QueryOrder;
use Doctrine\ORM\Mapping\ClassMetadata;

class FooEntity
{
    private $bar;
}

/**
 * @QueryOrder("name")
 */
class BarEntity
{
    private $name;

    /**
     * @QueryOrder("someField")
     */
    private $baz;
}

class BazEntity
{
    private $someField;

    private $faa;
}

class FaaEntity
{
    private $id;
}

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class AnnotationSortingFieldResolverTest extends \PHPUnit_Framework_TestCase
{
    private function createEm($sourceEntity, $assocProperty, $targetEntity)
    {
        $fooMetadata = $this->getMock('Doctrine\ORM\Mapping\ClassMetadata', array(), array(), '', false);
        $fooMetadata->expects($this->any())
                    ->method('getAssociationMapping')
                    ->with($assocProperty)
                    ->will($this->returnValue(array('targetEntity' => $targetEntity)));

        $em = $this->getMock('Doctrine\ORM\EntityManager', array(), array(), '', false);
        $em->expects($this->any())
           ->method('getClassMetadata')
           ->with($sourceEntity)
           ->will($this->returnValue($fooMetadata));

        return $em;
    }

    public function testResolve_definedOnRelatedEntity()
    {
        $source = 'Sli\ExtJsIntegrationBundle\Tests\Service\FooEntity';

        $r = new AnnotationSortingFieldResolver($this->createEm($source, 'bar', 'Sli\ExtJsIntegrationBundle\Tests\Service\BarEntity'));
        $this->assertEquals('name', $r->resolve($source, 'bar'));
    }

    public function testResolve_definedOnProperty()
    {
        $source = 'Sli\ExtJsIntegrationBundle\Tests\Service\BarEntity';

        $r = new AnnotationSortingFieldResolver($this->createEm($source, 'baz', 'Sli\ExtJsIntegrationBundle\Tests\Service\BazEntity'));
        $this->assertEquals('someField', $r->resolve($source, 'baz'));
    }

    public function testResolve_withDefaultProperty()
    {
        $source = 'Sli\ExtJsIntegrationBundle\Tests\Service\BazEntity';

        $r = new AnnotationSortingFieldResolver($this->createEm($source, 'faa', 'Sli\ExtJsIntegrationBundle\Tests\Service\FaaEntity'));
        $this->assertEquals('id', $r->resolve($source, 'faa'));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testResolve_withNonExistingDefaultProperty()
    {
        $source = 'Sli\ExtJsIntegrationBundle\Tests\Service\BazEntity';

        $r = new AnnotationSortingFieldResolver($this->createEm($source, 'faa', 'Sli\ExtJsIntegrationBundle\Tests\Service\FaaEntity'), 'blah');
        $r->resolve($source, 'faa');
    }
}
