<?php

namespace Sli\ExtJsIntegrationBundle\Tests\QueryBuilder\ResolvingAssociatedModelSortingField;

require_once __DIR__ . '/../../../Service/SortingFieldAnnotations.php';

use Sli\ExtJsIntegrationBundle\QueryBuilder\ResolvingAssociatedModelSortingField\AnnotationSortingFieldResolver;
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
    private function createDoctrineRegistry($sourceEntity, $assocProperty, $targetEntity)
    {
        $fooMetadata = $this->createMock('Doctrine\ORM\Mapping\ClassMetadata', array(), array(), '', false);
        $fooMetadata->expects($this->any())
            ->method('getAssociationMapping')
            ->with($assocProperty)
            ->will($this->returnValue(array('targetEntity' => $targetEntity)));

        $em = $this->createMock('Doctrine\ORM\EntityManager', array(), array(), '', false);
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->with($sourceEntity)
            ->will($this->returnValue($fooMetadata));

        $doctrineRegistry = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');

        $doctrineRegistry->expects($this->any())
                         ->method('getManagerForClass')
                         ->will($this->returnValue($em));

        return $doctrineRegistry;
    }

    public function testResolve_definedOnRelatedEntity()
    {
        $source = __NAMESPACE__ . '\FooEntity';

        $r = new AnnotationSortingFieldResolver($this->createDoctrineRegistry($source, 'bar', __NAMESPACE__ . '\BarEntity'));
        $this->assertEquals('name', $r->resolve($source, 'bar'));
    }

    public function testResolve_definedOnProperty()
    {
        $source = __NAMESPACE__ . '\BarEntity';

        $r = new AnnotationSortingFieldResolver($this->createDoctrineRegistry($source, 'baz', __NAMESPACE__ . '\BazEntity'));
        $this->assertEquals('someField', $r->resolve($source, 'baz'));
    }

    public function testResolve_withDefaultProperty()
    {
        $source = __NAMESPACE__. '\BazEntity';

        $r = new AnnotationSortingFieldResolver($this->createDoctrineRegistry($source, 'faa', __NAMESPACE__ . '\FaaEntity'));
        $this->assertEquals('id', $r->resolve($source, 'faa'));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testResolve_withNonExistingDefaultProperty()
    {
        $source = __NAMESPACE__ . '\BazEntity';

        $r = new AnnotationSortingFieldResolver($this->createDoctrineRegistry($source, 'faa', __NAMESPACE__ . '\FaaEntity'), 'blah');
        $r->resolve($source, 'faa');
    }
}
