<?php

namespace Sli\ExtJsIntegrationBundle\Tests\Service;

use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Mapping as Orm;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sli\ExtJsIntegrationBundle\Service\ExtjsQueryBuilder;
use Sli\ExtJsIntegrationBundle\Tests\AbstractDatabaseTestCase;
use Sli\ExtJsIntegrationBundle\Tests\DummyUser;

require_once __DIR__.'/../DummyEntities.php';

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class ExtjsQueryBuilderTest extends AbstractDatabaseTestCase
{
    public function testBuildQueryBuilderEmptyFilter()
    {
        $qb = self::$builder->buildQueryBuilder(DummyUser::clazz(), array(
        ));

        $users = $qb->getQuery()->getResult();
        $this->assertEquals(3, count($users));
        $this->assertEquals(1, $users[0]->id);
        $this->assertEquals(2, $users[1]->id);
        $this->assertEquals(3, $users[2]->id);
    }

    public function testBuildQueryBuilderWithEqFilter()
    {
        $qb = self::$builder->buildQueryBuilder(DummyUser::clazz(), array(
            'filter' => array(
                array('property' => 'id', 'value' => 'eq:1')
            )
        ));

        $users = $qb->getQuery()->getResult();
        $this->assertEquals(1, count($users));
        $this->assertEquals(1, $users[0]->id);
    }

    public function testBuildQueryBuilderWithInFilter()
    {
        $qb = self::$builder->buildQueryBuilder(DummyUser::clazz(), array(
            'filter' => array(
                array('property' => 'id', 'value' => 'in:1,3')
            )
        ));

        $users = $qb->getQuery()->getResult();
        $this->assertEquals(2, count($users));
        $this->assertEquals(1, $users[0]->id);
        $this->assertEquals(3, $users[1]->id);
    }

    public function testBuildQueryBuilderWithIsNotNullFilter()
    {
        $qb = self::$builder->buildQueryBuilder(DummyUser::clazz(), array(
            'filter' => array(
                array('property' => 'address', 'value' => 'isNull')
            )
        ));

        $users = $qb->getQuery()->getResult();
        $this->assertEquals(1, count($users));
    }

    public function testBuildQueryBuilderWithSortByDescWhereIdNotIn2()
    {
        $qb = self::$builder->buildQueryBuilder(DummyUser::clazz(), array(
            'sort' => array(
                array('property' => 'id', 'direction' => 'DESC')
            ),
            'filter' => array(
                array('property' => 'id', 'value' => 'notIn:2')
            )
        ));

        $users = $qb->getQuery()->getResult();
        $this->assertEquals(2, count($users));
        $this->assertEquals(3, $users[0]->id);
        $this->assertEquals(1, $users[1]->id);
    }

    public function testBuildQueryWithFetch()
    {
        $qb = self::$builder->buildQueryBuilder(DummyUser::clazz(), array(
            'fetch' => array(
                'address.country'
            )
        ));

        $this->assertEquals(2, count($qb->getDQLPart('select')));
    }

    public function testBuildQueryBuilderWhereUserAddressZip()
    {
        $qb = self::$builder->buildQueryBuilder(DummyUser::clazz(), array(
            'filter' => array(
                array('property' => 'lastname', 'value' => 'eq:doe'),
                array('property' => 'address.zip', 'value' => 'like:10%')
            )
        ));

        $users = $qb->getQuery()->getResult();
        $this->assertTrue(is_array($users));
        $this->assertEquals(1, count($users));
        /* @var DummyUser $user */
        $user = $users[0];
        $this->assertEquals('doe', $user->lastname);
        $this->assertNotNull($user->address);
        $this->assertEquals('1010', $user->address->zip);
    }

    public function testBuildQueryBuilderWithSkipAssocFilter()
    {
        $qb = self::$builder->buildQueryBuilder(DummyUser::clazz(), array(
            'filter' => array(
                array('property' => 'address', 'value' => 'eq:-')
            )
        ));

        $users = $qb->getQuery()->getResult();
        $this->assertEquals(3, count($users));
    }

    public function testBuildCountQueryBuilderFilterByAssociatedField()
    {
        $fetchQb = self::$builder->buildQueryBuilder(DummyUser::clazz(), array(
            'filter' => array(
                array('property' => 'address.zip', 'value' => 'like:10%')
            )
        ));

        $countQb = self::$builder->buildCountQueryBuilder($fetchQb);

        $this->assertEquals(1, $countQb->getQuery()->getSingleScalarResult());
    }

    public function testBuildCountQueryBuilderWithJoinFilterAndOrder()
    {
        $fetchQb = self::$builder->buildQueryBuilder(DummyUser::clazz(), array(
            'filter' => array(
                array('property' => 'address.zip', 'value' => 'isNull')
            ),
            'sort' => array( // it simply will be removed
                array('property' => 'address', 'direction' => 'DESC')
            )
        ));

        $countQb = self::$builder->buildCountQueryBuilder($fetchQb);

        $this->assertEquals(1, $countQb->getQuery()->getSingleScalarResult());
    }

    public function testBuildQueryOrderByAssociatedEntity()
    {
        $qb = self::$builder->buildQueryBuilder(DummyUser::clazz(), array(
            'sort' => array(
                array('property' => 'address', 'direction' => 'DESC')
            )
        ));

        /* @var DummyUser[] $users */
        $users = $qb->getQuery()->getResult();
        $this->assertEquals(3, count($users));

        $this->assertEquals('jane', $users[0]->firstname);
        $this->assertEquals('john', $users[1]->firstname);
        $this->assertEquals('vassily', $users[2]->firstname);
    }

    public function testBuildQueryOrderByAssociatedEntityWithProvidedSortingFieldResolver()
    {
        $sortingResolver = $this->getMock(
            '\Sli\ExtJsIntegrationBundle\QueryBuilder\ResolvingAssociatedModelSortingField\SortingFieldResolverInterface'
        );
        $sortingResolver->expects($this->atLeastOnce())
            ->method('resolve')
            ->with($this->equalTo(DummyUser::clazz()), $this->equalTo('address'))
            ->will($this->returnValue('street'));

        $qb = self::$builder->buildQueryBuilder(DummyUser::clazz(), array(
            'sort' => array(
                array('property' => 'address', 'direction' => 'ASC')
            )
        ), $sortingResolver);

        $orderBy = $qb->getDQLPart('orderBy');
        $this->assertEquals(1, count($orderBy));
        $this->assertTrue(strpos($orderBy[0], 'street ASC') !== false);

        /* @var DummyUser[] $users */
        $users = $qb->getQuery()->getResult();
        $this->assertEquals(3, count($users));

        $this->assertEquals('vassily', $users[0]->firstname);
        $this->assertEquals('jane', $users[1]->firstname);
        $this->assertEquals('john', $users[2]->firstname);
    }

    public function testBuildQueryWithMemberOfManyToMany()
    {
        $qb = self::$builder->buildQueryBuilder(DummyUser::clazz(), array(
            'filter' => array(
                // when IN is used in conjunction with TO_MANY ( MANY_TO_MANY, ONE_TO_MANY ) relations
                // then it will treated in special way and MEMBER OF query will be generated
                array('property' => 'groups', 'value' => 'in:1,20')
            )
        ));

        /* @var DummyUser[] $users */
        $users = $qb->getQuery()->getResult();
        $this->assertEquals(1, count($users));
        $this->assertEquals('john', $users[0]->firstname);
        $this->assertEquals('doe', $users[0]->lastname);
    }

    public function testBuildQueryWithNotMemberOfAndManyToMany()
    {
        $qb = self::$builder->buildQueryBuilder(DummyUser::clazz(), array(
            'filter' => array(
                array('property' => 'groups', 'value' => 'notIn:1')
            )
        ));

        /* @var DummyUser[] $users */
        $users = $qb->getQuery()->getResult();
        $this->assertEquals(2, count($users));
    }
}
