<?php

namespace Sli\ExtJsIntegrationBundle\QueryBuilder;

use Sli\ExtJsIntegrationBundle\QueryBuilder\Parsing\Expression;

/**
 * @internal
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */ 
class DqlCompiler
{
    private $exprMgr;

    public function __construct(ExpressionManager $exprMgr)
    {
        $this->exprMgr = $exprMgr;
    }

    public function compile(Expression $expression, DoctrineQueryBuilderParametersBinder $binder)
    {
        $result = '';

        if ($expression->getFunction()) {
            $compiledArgs = array();
            foreach ($expression->getFunctionArgs() as $arg) {
                if ($arg instanceof Expression) {
                    $compiledArgs[] = $this->compile($arg, $binder);
                } else {
                    $compiledArgs[] = $this->resolveArgument($arg, $binder);
                }
            }

            $result = $expression->getFunction() . '(' . implode(', ', $compiledArgs) . ')';
        } else {
            $result = $this->resolveArgument($expression->getExpression(), $binder);
        }

        if ($expression->getAlias()) {
            $result .= ' AS ' . ($expression->isHidden() ? 'HIDDEN ' : '') . $expression->getAlias();
        }

        return $result;
    }

    private function resolveArgument($arg, DoctrineQueryBuilderParametersBinder $binder)
    {
        if (':' == $arg{0}) { // a field is being referenced
            return $this->exprMgr->getDqlPropertyName(substr($arg, 1));
        } else {
            return '?' . ($binder->bind($arg) - 1);
        }
    }
}