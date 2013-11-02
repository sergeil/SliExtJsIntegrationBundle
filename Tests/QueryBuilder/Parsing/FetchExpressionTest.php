<?php

namespace Sli\ExtJsIntegrationBundle\Tests\Service\Parsing;
use Sli\ExtJsIntegrationBundle\QueryBuilder\Parsing\FetchExpression;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */ 
class FetchExpressionTest extends \PHPUnit_Framework_TestCase
{
    public function testSimpleExpressionWithoutAlias()
    {
        $expr = new FetchExpression(0, 'firstname');

        $this->assertEquals('firstname', $expr->getExpression());
        $this->assertFalse($expr->getAlias());
        $this->assertFalse($expr->getFunction());
    }

    public function testSimpleExpressionWithAlias()
    {
        $expr = new FetchExpression('fn', 'firstname');

        $this->assertEquals('firstname', $expr->getExpression());
        $this->assertEquals('fn', $expr->getAlias());
        $this->assertFalse($expr->getFunction());
    }

    public function testFunctionCallExpressionWithAlias()
    {
        $rawExpr = array(
            'function' => 'CONCAT',
            'args' => array(
                ':firstname',
                array(
                    'function' => 'CONCAT',
                    'args' => array(' ',':lastname')
                )
            )
        );
        $expr = new FetchExpression('fullname', $rawExpr);

        $this->assertEquals('fullname', $expr->getAlias());
        $this->assertSame($rawExpr, $expr->getExpression());
        $this->assertEquals($rawExpr['function'], $expr->getFunction());
        $this->assertTrue(is_array($expr->getFunctionArgs()));
        $args1 = $expr->getFunctionArgs();
        $this->assertEquals(2, count($args1));
        $this->assertEquals(':firstname', $args1[0]);
        $this->assertInstanceOf(FetchExpression::clazz(), $args1[1]);
        /* @var FetchExpression $fetchSubArg */
        $fetchSubArg = $args1[1];
        $this->assertFalse($fetchSubArg->getAlias());
        $args2 = $fetchSubArg->getFunctionArgs();
        $this->assertEquals(' ', $args2[0]);
        $this->assertEquals(':lastname', $args2[1]);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testHowWellFunctionNameIsValidated()
    {
        new FetchExpression(null, array('function' => '; SELECT'));
    }

    public function testSanitizeAlias()
    {
        $expr = new FetchExpression('; DELETE FROM xxx', 'fullname');

        $this->assertFalse($expr->getAlias());
    }
}