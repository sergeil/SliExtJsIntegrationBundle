<?php

namespace Sli\ExtJsIntegrationBundle\Tests\Service;

use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Mapping as Orm;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sli\ExtJsIntegrationBundle\Service\ExtjsQueryBuilder;

/**
 * @Orm\Entity
 * @Orm\Table(name="sli_extjsintegration_dummyuser")
 */
class DummyUser
{
    /**
     * @Orm\Id
     * @Orm\Column(type="integer")
     * @Orm\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Orm\Column(type="string", nullable=true)
     */
    public $firstname;

    /**
     * @Orm\Column(type="string", nullable=true)
     */
    public $lastname;

    static public function clazz()
    {
        return get_called_class();
    }
}

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class ExtjsQueryBuilderTest extends \Symfony\Bundle\FrameworkBundle\Test\WebTestCase
{
    /* @var \Doctrine\ORM\EntityManager */
    static private $em;

    /* @var \Sli\ExtJsIntegrationBundle\Service\ExtjsQueryBuilder $builder */
    static private $builder;

    static public function setUpBeforeClass()
    {
        /* @var \Symfony\Component\HttpKernel\Kernel $kernel */
        $kernel = static::createKernel();
        $kernel->boot();
        /* @var \Doctrine\ORM\EntityManager $em */
        $em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        $em = clone $em;

        self::$em = $em;

        // injecting a new metadata driver for our test entity
        $annotationDriver = new AnnotationDriver(
            $kernel->getContainer()->get('annotation_reader'),
            array(__DIR__)
        );

        $metadataFactory = $em->getMetadataFactory();
        $reflMetadataFactory = new \ReflectionClass($metadataFactory);
        $reflInitMethod = $reflMetadataFactory->getMethod('initialize');
        $reflInitMethod->setAccessible(true);
        $reflInitMethod->invoke($metadataFactory);
        $reflDriverProp = $reflMetadataFactory->getProperty('driver');
        $reflDriverProp->setAccessible(true);
        /* @var \Doctrine\ORM\Mapping\Driver\DriverChain $driver */
        $driver = $reflDriverProp->getValue($metadataFactory);
        $driver->addDriver($annotationDriver, __NAMESPACE__);

        $dummyUserMetadata = $metadataFactory->getMetadataFor(DummyUser::clazz());

        // updating database
        $st = new SchemaTool($em);
        $st->updateSchema(array($dummyUserMetadata), true);

        // populating
        foreach (array('john doe', 'jane doe', 'vassily pupkin') as $fullname) {
            $exp = explode(' ', $fullname);
            $e = new DummyUser();
            $e->firstname = $exp[0];
            $e->lastname = $exp[1];
            $em->persist($e);
        }
        $em->flush();

        self::$builder = $kernel->getContainer()->get('sli.extjsintegration.extjs_query_builder');
    }

    static public function tearDownAfterClass()
    {
        $dummyUserMetadata = self::$em->getClassMetadata(DummyUser::clazz());
        $st = new SchemaTool(self::$em);
        $st->dropSchema(array($dummyUserMetadata));
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
