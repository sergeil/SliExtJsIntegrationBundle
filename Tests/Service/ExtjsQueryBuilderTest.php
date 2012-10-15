<?php

namespace Sli\ExtJsIntegrationBundle\Tests\Service;

use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Mapping as Orm;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sli\ExtJsIntegrationBundle\Service\ExtjsQueryBuilder;

require_once __DIR__.'/DummyEntities.php';

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class ExtjsQueryBuilderTest extends AbstractDatabaseTestCase
{
    static public function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $metadataFactory = self::$em->getMetadataFactory();

        $dummyUserMetadata = $metadataFactory->getMetadataFor(DummyUser::clazz());
        $dummyAddressMetadata = $metadataFactory->getMetadataFor(DummyAddress::clazz());
        $dummyCountryMetadata = $metadataFactory->getMetadataFor(DummyCountry::clazz());

        // updating database
        $st = new SchemaTool(self::$em);
        $st->updateSchema(array($dummyUserMetadata, $dummyAddressMetadata, $dummyCountryMetadata), true);

        // populating
        foreach (array('john doe', 'jane doe', 'vassily pupkin') as $fullname) {
            $exp = explode(' ', $fullname);
            $e = new DummyUser();
            $e->firstname = $exp[0];
            $e->lastname = $exp[1];

            if ('john' == $exp[0]) {
                $address = new DummyAddress();
                $address->street = 'Blahblah';
                $address->zip = '1111111';
                $e->address = $address;
            }

            self::$em->persist($e);
        }
        self::$em->flush();

        self::$builder = self::$kernel->getContainer()->get('sli.extjsintegration.extjs_query_builder');
    }

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

    public function testBuildQueryBuilderWhereUserAddressZip()
    {
        $db = self::$builder->buildQueryBuilder(DummyUser::clazz(), array(
            'filter' => array(
                array('property' => 'lastname', 'value' => 'eq:doe'),
                array('property' => 'address.zip', 'value' => 'like:11%')
            )
        ));

        $users = $db->getQuery()->getResult();
        $this->assertTrue(is_array($users));
        $this->assertEquals(1, $users);
    }

    public function testBuildCountQueryBuilder()
    {
        $fetchQb = self::$builder->buildQueryBuilder(DummyUser::clazz(), array(
            'filter' => array(
                array('property' => 'lastname', 'value' => 'eq:doe')
            )
        ));

        $countQb = self::$builder->buildCountQueryBuilder($fetchQb);
        $this->assertEquals(2, $countQb->getQuery()->getSingleScalarResult());
    }
}
