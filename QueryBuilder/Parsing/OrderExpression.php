<?php

namespace Sli\ExtJsIntegrationBundle\QueryBuilder\Parsing;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */ 
class OrderExpression 
{
    private $input;
    private $parsedInput;

    public function __construct(array $input)
    {
        $this->input = $input;
        $this->parsedInput = $this->parse($input);
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return strlen($this->parsedInput['property']) > 0
            && in_array(strtoupper($this->parsedInput['direction']), array('ASC', 'DESC'));
    }

    private function parse(array $input)
    {
        $parsed = array(
            'property' => null,
            'direction' => null
        );

        if (isset($input['property'])) {
            $parsed['property'] = $input['property'];
        }
        if (isset($input['direction'])) {
            $parsed['direction'] = $input['direction'];
        }

        return $parsed;
    }

    /**
     * @return string|null
     */
    public function getProperty()
    {
        return $this->parsedInput['property'];
    }

    /**
     * @return string|null
     */
    public function getDirection()
    {
        return $this->parsedInput['direction'];
    }
}