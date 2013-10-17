<?php

namespace Sli\ExtJsIntegrationBundle\QueryBuilder\Parsing;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class Filter implements FilterInterface
{
    // supported comparators:
    const COMPARATOR_EQUAL = 'eq';
    const COMPARATOR_NOT_EQUAL = 'neq';
    const COMPARATOR_LIKE = 'like';
    const COMPARATOR_NOT_LIKE = 'notLike';
    const COMPARATOR_GREATER_THAN = 'gt';
    const COMPARATOR_GREATER_THAN_OR_EQUAL = 'gte';
    const COMPARATOR_LESS_THAN = 'lt';
    const COMPARATOR_LESS_THAN_OR_EQUAL = 'lte';
    const COMPARATOR_IN = 'in';
    const COMPARATOR_NOT_IN = 'notIn';
    const COMPARATOR_IS_NULL = 'isNull';
    const COMPARATOR_IS_NOT_NULL = 'isNotNull';

    private $input;
    private $parsedInput;

    static private $supportedComparators;

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
        if (!self::$supportedComparators) {
            self::$supportedComparators = array();

            $reflClass = new \ReflectionClass(__CLASS__);
            foreach ($reflClass->getConstants() as $name=>$value) {
                self::$supportedComparators[] = $value;
            }
        }

        return self::$supportedComparators;
    }

    /**
     * @param string $property
     * @param string $comparator
     * @param mixed|null $value  Value can be omitted when comparator is COMPARATOR_IS_NULL or COMPARATOR_IS_NOT_NULL
     *
     * @return Filter
     */
    static public function create($property, $comparator, $value = null)
    {
        $raw = null;

        if (in_array($comparator, array(self::COMPARATOR_IS_NULL, self::COMPARATOR_IS_NOT_NULL))) {
            $raw = array('property' => $property, 'value' => $comparator);
        } else {
            $raw = array('property' => $property, 'value' => "$comparator:$value");
        }

        return new self($raw);
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        $isValid = !!$this->parsedInput['property'];

        // TODO make sure that OR-ed "comparators" are also valid
        $isValid = $isValid && (   (null === $this->parsedInput['comparator'] && is_array($this->parsedInput['value']))
                                || (null !== $this->parsedInput['comparator'] && in_array($this->parsedInput['comparator'], self::getSupportedComparators())));

        return $isValid;
    }

    private function parseStringFilterValue($value)
    {
        $parsed = array();

        $separatorPosition = strpos($value, ':');

        if (false === $separatorPosition && in_array($value, array(self::COMPARATOR_IS_NULL, self::COMPARATOR_IS_NOT_NULL))) {
            $parsed['comparator'] = $value;
        } else {
            $parsed['comparator'] = substr($value, 0, $separatorPosition);
            $parsed['value'] = substr($value, $separatorPosition + 1);

            if (in_array($parsed['comparator'], array(self::COMPARATOR_IN, self::COMPARATOR_NOT_IN))) {
                $parsed['value'] = '' != $parsed['value'] ? explode(',', $parsed['value']) : array();
            }
        }

        return $parsed;
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
        if (isset($input['value'])) {
            if (is_string($input['value'])) {
                $parsed = array_merge($parsed, $this->parseStringFilterValue($input['value']));
            } else if (is_array($input['value'])) {
                $parsed['value'] = array();
                foreach ($input['value'] as $value) {
                    $parsed['value'][] = $this->parseStringFilterValue($value);
                }
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