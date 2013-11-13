<?php

namespace Sli\ExtJsIntegrationBundle\QueryBuilder\Parsing;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */ 
class Expression
{
    private $alias = false;
    private $function = false;
    private $functionArgs = array();
    private $expression;

    /**
     * @param string|array $expression
     * @param null $alias  Alias should only be provided when expression is used in SELECT
     */
    public function __construct($expression, $alias = null)
    {
        if (is_array($expression) && isset($expression['function']) && strlen($expression['function']) > 0) {
            $this->validateFunctionName($expression['function']);
            $this->function = $expression['function'];

            if (isset($expression['args']) && is_array($expression['args'])) {
                foreach ($expression['args'] as $arg) {
                    if (is_array($arg)) {
                        $this->functionArgs[] = new self($arg);
                    } else {
                        $this->functionArgs[] = $arg;
                    }
                }
            }
        }

        $this->expression = $expression;

        if (   is_string($alias) && strlen($alias) > 1
            && preg_match('/^\w+$/', $alias{0}) && preg_match('/^[\w0-9_]+$/', $alias)) {

            $this->alias = $alias;
        }
    }

    private function validateFunctionName($functionName)
    {
        if (!preg_match('/^[\w_0-9]+$/', $functionName)) {
            throw new \RuntimeException("'$functionName' is not a properly formatted function name!");
        }
    }

    /**
     * @return string|array
     */
    public function getExpression()
    {
        return $this->expression;
    }

    /**
     * @return string|false
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return string|false
     */
    public function getFunction()
    {
        return $this->function;
    }

    /**
     * @return array
     */
    public function getFunctionArgs()
    {
        return $this->functionArgs;
    }

    static public function clazz()
    {
        return get_called_class();
    }
}