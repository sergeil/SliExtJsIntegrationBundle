<?php

namespace Sli\ExtJsIntegrationBundle\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Mapping as Orm;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sli\ExtJsIntegrationBundle\Service\ExtjsQueryBuilder;
use Sli\ExtJsIntegrationBundle\Tests\CreditCard;
use Sli\ExtJsIntegrationBundle\Tests\DummyAddress;
use Sli\ExtJsIntegrationBundle\Tests\DummyCountry;
use Sli\ExtJsIntegrationBundle\Tests\DummyUser;
use Sli\ExtJsIntegrationBundle\Tests\Group;
use Symfony\Bridge\Doctrine\RegistryInterface;

require_once __DIR__.'/DummyEntities.php';

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class AbstractDatabaseTestCase extends \Symfony\Bundle\FrameworkBundle\Test\WebTestCase
{
    /* @var \Doctrine\ORM\EntityManager */
    static protected $em;

    /* @var \Sli\ExtJsIntegrationBundle\Service\ExtjsQueryBuilder $builder */
    static protected $builder;

    /* @var \Symfony\Component\HttpKernel\Kernel $kernel */
    static protected $kernel;

    protected function tearDown()
    {
        // overriding this method prevents kernel from shutting down, so our hacked annotation driver from
        // setUpBeforeClass remains used
    }

    static public function setUpBeforeClass()
    {
        /* @var \Symfony\Component\HttpKernel\Kernel $kernel */
        self::$kernel = static::createKernel();
        self::$kernel->boot();

        /* @var RegistryInterface $doctrine */
        $doctrine = self::$kernel->getContainer()->get('doctrine');

        $em = $doctrine->getManager();

        self::$em = $em;
        self::$builder = self::$kernel->getContainer()->get('sli.extjsintegration.extjs_query_builder');

        // injecting a new metadata driver for our test entity
        $annotationDriver = new AnnotationDriver(
            self::$kernel->getContainer()->get('annotation_reader'),
            array(__DIR__)
        );

        $metadataFactory = $em->getMetadataFactory();
        $reflMetadataFactory = new \ReflectionClass($metadataFactory);
        $reflInitMethod = $reflMetadataFactory->getMethod('initialize');
        $reflInitMethod->setAccessible(true);
        $reflInitMethod->invoke($metadataFactory);

        // dynamically adding a namespace
        $reflDriverProp = $reflMetadataFactory->getProperty('driver');
        $reflDriverProp->setAccessible(true);
        /* @var \Doctrine\ORM\Mapping\Driver\DriverChain $driver */
        $driver = $reflDriverProp->getValue($metadataFactory);
        $driver->addDriver($annotationDriver, __NAMESPACE__);

        // adding dummy data & updating database

        $st = new SchemaTool(self::$em);

        $classNames = array(
            DummyUser::clazz(), DummyAddress::clazz(), DummyCity::clazz(), DummyCountry::clazz(),
            CreditCard::clazz(), Group::clazz(), DummyOrder::clazz(), President::clazz()
        );
        $meta = array();
        foreach ($classNames as $className) {
            $meta[] = $metadataFactory->getMetadataFor($className);
        }

        $st->updateSchema($meta, true);

        $adminsGroup = new Group();
        $adminsGroup->name = 'admins';
        $em->persist($adminsGroup);

        $users = array();

        // populating
        foreach (array('john doe', 'jane doe', 'vassily pupkin') as $fullname) {
            $exp = explode(' ', $fullname);
            $user = new DummyUser();
            $user->firstname = $exp[0];
            $user->lastname = $exp[1];

            if ('john' == $exp[0]) {
                $adminsGroup->addUser($user);

                $address = new DummyAddress();
                $address->country = new DummyCountry();
                $address->country->name = 'A';

                $address->street = 'foofoo';
                $address->zip = '1010';
                $user->address = $address;
            } else if ('jane' == $exp[0]) {
                $address = new DummyAddress();
                $address->country = new DummyCountry();
                $address->country->name = 'B';
                $address->zip = '2020';
                $address->street = 'Blahblah';

                $user->address = $address;
            }

            $users[] = $user;

            self::$em->persist($user);
        }

        $o1 = new DummyOrder();
        $o1->number = 'ORDER-1';
        $o1->user = $users[0];

        $o2 = new DummyOrder();
        $o2->number = 'ORDER-2';
        $o2->user = $users[1];

        $hourPlus = new \DateTime('now');
        $hourPlus = $hourPlus->modify('+1 hour');

        $president = new President();
        $president->since = $hourPlus;

        self::$em->persist($o1);
        self::$em->persist($o2);
        self::$em->persist($president);

        self::$em->flush();
    }

    static public function tearDownAfterClass()
    {
        $orderUserMetadata = self::$em->getClassMetadata(DummyOrder::clazz());
        $dummyUserMetadata = self::$em->getClassMetadata(DummyUser::clazz());
        $dummyAddressMetadata = self::$em->getClassMetadata(DummyAddress::clazz());
        $dummyCountryMetadata = self::$em->getClassMetadata(DummyCountry::clazz());
        $dummyCCMetadata = self::$em->getClassMetadata(CreditCard::clazz());
        $groupMetadata = self::$em->getClassMetadata(Group::clazz());
        $presidentMetadata = self::$em->getClassMetadata(President::clazz());
        $cityMetadata = self::$em->getClassMetadata(DummyCity::clazz());

        $st = new SchemaTool(self::$em);
        $st->dropSchema(array(
            $orderUserMetadata, $dummyUserMetadata, $dummyAddressMetadata,
            $dummyCountryMetadata, $dummyCCMetadata, $groupMetadata,
            $presidentMetadata, $cityMetadata
        ));

        self::ensureKernelShutdown(); // we need to do this menually here because we have overridden "tearDown" method
    }
}
