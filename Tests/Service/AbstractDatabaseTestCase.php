<?php

namespace Sli\ExtJsIntegrationBundle\Tests\Service;

use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Mapping as Orm;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sli\ExtJsIntegrationBundle\Service\ExtjsQueryBuilder;

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

    static public function setUpBeforeClass()
    {
        /* @var \Symfony\Component\HttpKernel\Kernel $kernel */
        self::$kernel = static::createKernel();
        self::$kernel->boot();
        /* @var \Doctrine\ORM\EntityManager $em */
        $em = self::$kernel->getContainer()->get('doctrine.orm.entity_manager');
        $em = clone $em;

        self::$em = $em;

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
        $reflDriverProp = $reflMetadataFactory->getProperty('driver');
        $reflDriverProp->setAccessible(true);
        /* @var \Doctrine\ORM\Mapping\Driver\DriverChain $driver */
        $driver = $reflDriverProp->getValue($metadataFactory);
        $driver->addDriver($annotationDriver, __NAMESPACE__);
    }

    static public function tearDownAfterClass()
    {
        $dummyUserMetadata = self::$em->getClassMetadata(DummyUser::clazz());
        $dummyAddressMetadata = self::$em->getClassMetadata(DummyAddress::clazz());
        $dummyCountryMetadata = self::$em->getClassMetadata(DummyCountry::clazz());

        $st = new SchemaTool(self::$em);
        $st->dropSchema(array($dummyUserMetadata, $dummyAddressMetadata, $dummyCountryMetadata));
    }
}
