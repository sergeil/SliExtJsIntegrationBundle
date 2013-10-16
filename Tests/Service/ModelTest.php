<?php

namespace Sli\ExtJsIntegrationBundle\Tests\Service;

use Doctrine\ORM\Mapping as Orm;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sli\ExtJsIntegrationBundle\Service\ExpressionManager;

require_once __DIR__.'/DummyEntities.php';

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class ExpressionManagerTest  extends AbstractDatabaseTestCase
{
    /* @var \Sli\ExtJsIntegrationBundle\Service\Model */
    private $model;

    public function setUp()
    {
        $this->model = new ExpressionManager(DummyUser::clazz(), self::$em);
    }

    public function testIsValidExpression()
    {
        $this->assertTrue($this->model->isValidExpression('address.zip'));
        $this->assertTrue($this->model->isValidExpression('address.street'));
        $this->assertTrue($this->model->isValidExpression('address.country'));
        $this->assertFalse($this->model->isValidExpression('address.foo'));

        $this->assertTrue($this->model->isValidExpression('address'));
        $this->assertTrue($this->model->isValidExpression('firstname'));
        $this->assertFalse($this->model->isValidExpression('bar'));
    }

    public function testResolveUnexistingAlias()
    {
        $this->assertNull($this->model->resolveAliasToExpression('jx'));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testAllocateAliasForNotExistingAssociation()
    {
        $this->model->allocateAlias('address.foo');
    }

    public function testAllocateAliasAndThenResolveAliasToExpression()
    {
        $alias = $this->model->allocateAlias('address.country');
        $this->assertNotNull($alias);
        $this->assertSame('address.country', $this->model->resolveAliasToExpression($alias));
    }

    public function testGetDqlPropertyName()
    {
        $this->assertEquals('e.firstname', $this->model->getDqlPropertyName('firstname'));
        $this->assertEquals('j1.name', $this->model->getDqlPropertyName('address.country.name'));
        $this->assertEquals('j0.zip', $this->model->getDqlPropertyName('address.zip'));
    }

    public function testInjectJoinsAndExecuteQuery()
    {
        $qb = self::$em->createQueryBuilder();
        $qb->select('e')
           ->from(DummyUser::clazz(), 'e');

        $dqlPropName = $this->model->getDqlPropertyName('address.country.name');
        $this->model->injectJoins($qb);

        $dqlParts = $qb->getDQLParts();

        $this->assertArrayHasKey('e', $dqlParts['join']);
        $this->assertEquals(2, count($dqlParts['join']['e']));
        $this->assertEquals(3, count($dqlParts['select']));
    }

    public function testGetMapping()
    {
        $addressMapping = $this->model->getMapping('address');
        $addressCountry  = $this->model->getMapping('address.country');
        $addressZip = $this->model->getMapping('address.zip');
        $firstname = $this->model->getMapping('firstname');

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
        $this->assertTrue($this->model->isAssociation('address'));
        $this->assertTrue($this->model->isAssociation('address.country'));
        $this->assertFalse($this->model->isAssociation('address.zip'));
    }
}
