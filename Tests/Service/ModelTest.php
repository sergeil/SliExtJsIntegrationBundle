<?php

namespace Sli\ExtJsIntegrationBundle\Tests\Service;

use Doctrine\ORM\Mapping as Orm;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sli\ExtJsIntegrationBundle\Service\Model;

require_once __DIR__.'/DummyEntities.php';

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class ModelTest  extends AbstractDatabaseTestCase
{
    /* @var \Sli\ExtJsIntegrationBundle\Service\Model */
    private $model;

    public function setUp()
    {
        $this->model = new Model(DummyUser::clazz(), self::$em);
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
        $this->assertNull($this->model->resolveAlias('jx'));
    }

    public function testAllocateAliasAndThenResolveAlias()
    {
        $alias = $this->model->allocateAlias('address.country.name');
        $this->assertNotNull($alias);
        $this->assertSame('address.country.name', $this->model->resolveAlias($alias));
    }

    public function testGetDqlPropertyName()
    {
        $this->assertEquals('e.firstname', $this->model->getDqlPropertyName('firstname'));
        $this->assertEquals('j1.name', $this->model->getDqlPropertyName('address.country.name'));
        $this->assertEquals('j0.zip', $this->model->getDqlPropertyName('address.zip'));
    }
}
