<?php

namespace Sli\ExtJsIntegrationBundle\Tests\Service;

use Doctrine\ORM\QueryBuilder;
use Sli\ExtJsIntegrationBundle\QueryBuilder\DoctrineQueryBuilderParametersBinder;
use Sli\ExtJsIntegrationBundle\QueryBuilder\DqlCompiler;
use Sli\ExtJsIntegrationBundle\QueryBuilder\ExpressionManager;
use Sli\ExtJsIntegrationBundle\QueryBuilder\Parsing\Expression;
use Sli\ExtJsIntegrationBundle\Tests\AbstractDatabaseTestCase;
use Sli\ExtJsIntegrationBundle\Tests\DummyUser;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */ 
class DqlCompilerTest extends AbstractDatabaseTestCase
{
    /* @var ExpressionManager */
    private $exprMgr;
    /* @var DoctrineQueryBuilderParametersBinder */
    private $binder;
    /* @var QueryBuilder */
    private $qb;
    /* @var DqlCompiler */
    private $compiler;

    public function setUp()
    {
        $this->qb = self::$em->createQueryBuilder();
        $this->exprMgr = new ExpressionManager(DummyUser::clazz(), self::$em);
        $this->binder = new DoctrineQueryBuilderParametersBinder($this->qb);
        $this->compiler = new DqlCompiler($this->exprMgr);
    }

    public function testCompileSimple()
    {
        $compileExpression = $this->compiler->compile(new Expression(':firstname', 'fn'), $this->binder);

        $this->assertEquals('e.firstname AS fn', $compileExpression);
    }

    public function testCompileFunction()
    {
        $rawExpr = array(
            'function' => 'CONCAT',
            'args' => array(
                ':firstname',
                array(
                    'function' => 'CONCAT',
                    'args' => array(
                        ' ', ':lastname'
                    )
                )
            )
        );
        $expr = new Expression($rawExpr, 'fullname');

        $compiledExpression = $this->compiler->compile($expr, $this->binder);

        $this->assertEquals('CONCAT(e.firstname, CONCAT(?0, e.lastname)) AS fullname', $compiledExpression);
    }
}