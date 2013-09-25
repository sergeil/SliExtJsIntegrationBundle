<?php

namespace Sli\ExtJsIntegrationBundle\Tests\QueryBuilder\ResolvingAssociatedModelSortingField;
use Sli\ExtJsIntegrationBundle\QueryBuilder\ResolvingAssociatedModelSortingField\ChainSortingFieldResolver;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class ChainSortingFieldResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testAdd_and_All_methods()
    {
        $c = new ChainSortingFieldResolver();

        $this->assertEquals(0, count($c->all()));

        $resolver = $this->getMock(
            '\Sli\ExtJsIntegrationBundle\QueryBuilder\ResolvingAssociatedModelSortingField\SortingFieldResolverInterface'
        );

        $c->add($resolver);

        $resolvers = $c->all();

        $this->assertTrue(is_array($resolvers));
        $this->assertArrayHasKey(0, $resolvers);
        $this->assertSame($resolver, $resolvers[0]);
    }

    private function createResolver($entityFqcn, $paramName, $resultValue)
    {
        $resolver = $this->getMock(
            '\Sli\ExtJsIntegrationBundle\QueryBuilder\ResolvingAssociatedModelSortingField\SortingFieldResolverInterface'
        );
        $resolver->expects($this->once())
                 ->method('resolve')
                 ->with($this->equalTo($entityFqcn), $this->equalTo($paramName))
                 ->will($this->returnValue($resultValue));

        return $resolver;
    }

    public function testAdd_andThen_resolve()
    {
        $entityFqcn = 'foo';
        $paramName = 'barProperty';

        $resolver1 = $this->createResolver($entityFqcn, $paramName, null);
        $resolver2 = $this->createResolver($entityFqcn, $paramName, null);
        $resolver3 = $this->createResolver($entityFqcn, $paramName, 'fooResult');
        $resolver4 = $this->getMock(
            '\Sli\ExtJsIntegrationBundle\QueryBuilder\ResolvingAssociatedModelSortingField\SortingFieldResolverInterface'
        );
        $resolver4->expects($this->never())->method('resolve');

        $c = new ChainSortingFieldResolver();
        $c->add($resolver1);
        $c->add($resolver2);
        $c->add($resolver3);
        $c->add($resolver4);

        $this->assertEquals('fooResult', $c->resolve($entityFqcn, $paramName));
    }
}