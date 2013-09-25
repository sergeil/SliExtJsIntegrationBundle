<?php

namespace Sli\ExtJsIntegrationBundle\Tests\Service;

use Doctrine\ORM\Mapping as Orm;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sli\ExtJsIntegrationBundle\QueryBuilder\ExpressionManager;
use Sli\ExtJsIntegrationBundle\Tests\AbstractDatabaseTestCase;
use Sli\ExtJsIntegrationBundle\Tests\DummyAddress;
use Sli\ExtJsIntegrationBundle\Tests\DummyCountry;
use Sli\ExtJsIntegrationBundle\Tests\DummyUser;

require_once __DIR__.'/../DummyEntities.php';

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class ExpressionManagerTest  extends AbstractDatabaseTestCase
{
    /* @var ExpressionManager */
    private $exprMgr;

    public function setUp()
    {
        $this->exprMgr = new ExpressionManager(DummyUser::clazz(), self::$em);
    }

    public function testIsValidExpression()
    {
        $this->assertTrue($this->exprMgr->isValidExpression('address.zip'));
        $this->assertTrue($this->exprMgr->isValidExpression('address.street'));
        $this->assertTrue($this->exprMgr->isValidExpression('address.country'));
        $this->assertFalse($this->exprMgr->isValidExpression('address.foo'));

        $this->assertTrue($this->exprMgr->isValidExpression('address'));
        $this->assertTrue($this->exprMgr->isValidExpression('firstname'));
        $this->assertFalse($this->exprMgr->isValidExpression('bar'));
    }

    public function testResolveUnexistingAlias()
    {
        $this->assertNull($this->exprMgr->resolveAliasToExpression('jx'));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testAllocateAliasForNotExistingAssociation()
    {
        $this->exprMgr->allocateAlias('address.foo');
    }

    public function testAllocateAliasAndThenResolveAliasToExpression()
    {
        $alias = $this->exprMgr->allocateAlias('address.country');
        $this->assertNotNull($alias);
        $this->assertSame('address.country', $this->exprMgr->resolveAliasToExpression($alias));
    }

    public function testGetDqlPropertyName()
    {
        $this->assertEquals('e.firstname', $this->exprMgr->getDqlPropertyName('firstname'));
        $this->assertEquals('j1.name', $this->exprMgr->getDqlPropertyName('address.country.name'));
        $this->assertEquals('j0.zip', $this->exprMgr->getDqlPropertyName('address.zip'));
    }

    public function testInjectJoinsAndExecuteQuery()
    {
        $qb = self::$em->createQueryBuilder();
        $qb->select('e')
           ->from(DummyUser::clazz(), 'e');

        $dqlPropName = $this->exprMgr->getDqlPropertyName('address.country.name');
        $this->exprMgr->injectJoins($qb);

        $dqlParts = $qb->getDQLParts();

        $this->assertArrayHasKey('e', $dqlParts['join']);
        $this->assertEquals(2, count($dqlParts['join']['e']));
        $this->assertEquals(3, count($dqlParts['select']));
    }

    public function testGetMapping()
    {
        $addressMapping = $this->exprMgr->getMapping('address');
        $addressCountry  = $this->exprMgr->getMapping('address.country');
        $addressZip = $this->exprMgr->getMapping('address.zip');
        $firstname = $this->exprMgr->getMapping('firstname');

        $this->assertNotNull($addressMapping);
        $this->assertTrue(is_array($addressMapping));
        $this->assertArrayHasKey('targetEntity', $addressMapping);
        $this->assertEquals(DummyAddress::clazz(), $addressMapping['targetEntity']);

        $this->assertNotNull($addressCountry);
        $this->assertTrue(is_array($addressCountry));
        $this->assertArrayHasKey('targetEntity', $addressCountry);
        $this->assertEquals(DummyCountry::clazz(), $addressCountry['targetEntity']);

        $this->assertNotNull($addressZip);
        $this->assertTrue(is_array($addressZip));
        $this->assertArrayHasKey('fieldName', $addressZip);
        $this->assertEquals('zip', $addressZip['fieldName']);

        $this->assertNotNull($firstname);
        $this->assertTrue(is_array($firstname));
        $this->assertArrayHasKey('fieldName', $firstname);
        $this->assertEquals('firstname', $firstname['fieldName']);
    }

    public function testIsAssociation()
    {
        $this->assertTrue($this->exprMgr->isAssociation('address'));
        $this->assertTrue($this->exprMgr->isAssociation('address.country'));
        $this->assertFalse($this->exprMgr->isAssociation('address.zip'));
    }
}
