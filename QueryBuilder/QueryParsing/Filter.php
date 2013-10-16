<?php

namespace Sli\ExtJsIntegrationBundle\QueryBuilder\QueryParsing;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class Filter 
{
    const COMPARATOR_EQUAL = 'eq';
    const COMPARATOR_LIKE = 'like';
    const COMPARATOR_GREATER_THAN = 'gt';
    const COMPARATOR_LESS_THAN = 'lt';
    const COMPARATOR_IN = 'in';
    const COMPARATOR_NOT_IN = 'notIn';
    const COMPARATOR_IS_NULL = 'isNull';
    const COMPARATOR_IS_NOT_NULL = 'isNotNull';

    private $input;
    private $parsedInput;

    public function __construct(array $input)
    {
        $this->input = $input;
        $this->parsedInput = $this->parse($input);
    }

    /**
     * @return string[]
     */
    static public function getSupportedComparators()
    {
        return array(
            self::COMPARATOR_EQUAL,
            self::COMPARATOR_LIKE,
            self::COMPARATOR_GREATER_THAN,
            self::COMPARATOR_LESS_THAN,
            self::COMPARATOR_IS_NULL,
            self::COMPARATOR_IS_NOT_NULL
        );
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return !!$this->parsedInput['property'] && in_array($this->parsedInput['comparator'], self::getSupportedComparators());
    }

    private function parse(array $input)
    {
        $parsed = array(
            'property' => null,
            'comparator' => null,
            'value' => null
        );

        if (isset($input['property'])) {
            $parsed['property'] = $input['property'];
        }
        if (isset($input['value']) && is_string($input['value'])) {
            $separatorPosition = strpos($input['value'], ':');

            if (false === $separatorPosition && in_array($input['value'], array(self::COMPARATOR_IS_NULL, self::COMPARATOR_IS_NOT_NULL))) {
                $parsed['comparator'] = $input['value'];
            } else {
                $parsed['comparator'] = substr($input['value'], 0, $separatorPosition);
                $parsed['value'] = substr($input['value'], $separatorPosition + 1);
            }
        }

        return $parsed;
    }

    /**
     * @return array
     */
    public function compile()
    {
        $value = null;
        if (in_array($this->parsedInput['comparator'], array(self::COMPARATOR_IS_NULL, self::COMPARATOR_IS_NOT_NULL))) {
            $value = $this->parsedInput['comparator'];
        } else {
            $value = $this->parsedInput['comparator'] . ':' . $this->parsedInput['value'];
        }

        return array(
            'property' => $this->parsedInput['property'],
            'value' => $value
        );
    }

    public function getProperty()
    {
        return $this->parsedInput['property'];
    }

    /**
     * @param string $property
     * @return Filter
     */
    public function setProperty($property)
    {
        $this->parsedInput['property'] = $property;

        return $this;
    }

    public function getValue()
    {
        return $this->parsedInput['value'];
    }

    /**
     * @param string $value
     * @return Filter
     */
    public function setValue($value)
    {
        $this->parsedInput['value'] = $value;

        return $this;
    }

    public function getComparator()
    {
        return $this->parsedInput['comparator'];
    }

    /**
     * @param string $comparator
     * @return Filter
     */
    public function setComparator($comparator)
    {
        $this->parsedInput['comparator'] = $comparator;

        return $this;
    }

    static public function clazz()
    {
        return get_called_class();
    }
}