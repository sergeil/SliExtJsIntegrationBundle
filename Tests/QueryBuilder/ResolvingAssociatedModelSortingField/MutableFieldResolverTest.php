<?php

namespace Sli\ExtJsIntegrationBundle\Tests\QueryBuilder\ResolvingAssociatedModelSortingField;

use Sli\ExtJsIntegrationBundle\QueryBuilder\ResolvingAssociatedModelSortingField\MutableSortingFieldResolver;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class MutableSortingFieldResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testAddAndResolveMethods()
    {
        $s = new MutableSortingFieldResolver();

        $this->assertNull($s->resolve('foo', 'blah'));

        $s->add('FooEntity', 'fooProperty', 'result-yo');

        $this->assertEquals('result-yo', $s->resolve('FooEntity', 'fooProperty'));

        $this->assertNull($s->resolve('FooEntity', 'barProperty'));

        $s->add('FooEntity', 'barProperty', 'result-yo2');

        $this->assertEquals('result-yo2', $s->resolve('FooEntity', 'barProperty'));
    }
}