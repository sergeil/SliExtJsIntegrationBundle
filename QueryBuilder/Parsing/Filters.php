<?php

namespace Sli\ExtJsIntegrationBundle\QueryBuilder\Parsing;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class Filters implements \Iterator, \Countable, \ArrayAccess
{
    private $filters = array();
    private $iteratorIndex = 0;

    public function __construct(array $filters)
    {
        foreach ($filters as $rawFilter) {
            if ($this->isOrFilterDefinition($rawFilter)) {
                $this->addOrFilter(new OrFilter($rawFilter));
            } else {
                $this->addFilter(new Filter($rawFilter));
            }
        }
    }

    private function isOrFilterDefinition(array $rawFiler)
    {
        return !isset($rawFiler['property']) && !isset($rawFiler['value']);
    }

    /**
     * @param string $property
     *
     * @return Filter[]
     */
    public function findByProperty($property)
    {
        $result = array();

        foreach ($this->filters as $filter) {
            if ($filter instanceof Filter && $filter->getProperty() == $property) {
                $result[] = $filter;
            }
        }

        return $result;
    }

    /**
     * @throws \RuntimeException
     *
     * @param string $property
     *
     * @return Filter|null
     */
    public function findOneByProperty($property)
    {
        $result = $this->findByProperty($property);

        if (count($result) > 1) {
            throw new \RuntimeException(sprintf(
                "It was expected that property '%s' would have only one filter defined for it, but in fact it has %d.",
                $property, count($result)
            ));
        } else if (count($result) == 1) {
            return $result[0];
        }

        return null;
    }

    /**
     * @param Filter $filter
     *
     * @return bool
     */
    public function addFilter(Filter $filter)
    {
        $isAlreadyAdded = false;
        foreach ($this->filters as $currentFilter) {
            if ($currentFilter === $filter) {
                $isAlreadyAdded = true;
            }
        }

        if (!$isAlreadyAdded) {
            $this->filters[] = $filter;
        }
    }

    /**
     * @param string $property
     * @return bool
     */
    public function hasFilterForProperty($property)
    {
        return count($this->findByProperty($property)) !== 0;
    }

    /**
     * @param Filter $filter
     * @return bool
     */
    public function removeFilter(Filter $filter)
    {
        $isRemoved = false;

        $siftedResult = array();
        foreach ($this->filters as $currentFilter) {
            if ($currentFilter === $filter) {
                $isRemoved = true;

                continue;
            }

            $siftedResult[] = $currentFilter;
        }

        $this->filters = $siftedResult;

        return $isRemoved;
    }

    /**
     * @return array
     */
    public function compile()
    {
        $result = array();

        foreach ($this->filters as $filter) {
            /* @var FilterInterface $filter */
            $result[] = $filter->compile();
        }

        return $result;
    }

    /**
     * @param OrFilter $orFilter
     */
    public function addOrFilter(OrFilter $orFilter)
    {
        foreach ($this->filters as $currentFilter) {
            if ($orFilter === $currentFilter) {
                return false;
            }
        }

        $this->filters[] = $orFilter;

        return true;
    }

    public function removeOrFilter(OrFilter $orFilter)
    {
        $sifterFilters = array();
        foreach ($this->filters as $currentFilter) {
            if ($currentFilter === $orFilter) {
                continue;
            }

            $sifterFilters[] = $currentFilter;
        }

        $this->filters = $sifterFilters;
    }

    // Iterator:

    public function current()
    {
        return $this->filters[$this->iteratorIndex];
    }

    public function next()
    {
        $this->iteratorIndex++;
    }

    public function key()
    {
        return $this->iteratorIndex;
    }

    public function valid()
    {
        return isset($this->filters[$this->iteratorIndex]);
    }

    public function rewind()
    {
        $this->iteratorIndex = 0;
        reset($this->filters);
    }

    // Countable:

    public function count()
    {
        return count($this->filters);
    }

    // ArrayAccess

    public function offsetExists($offset)
    {
        return isset($this->filters[$offset]);
    }
    public function offsetGet($offset)
    {
        return $this->filters[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->filters[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->filters[$offset]);
    }
}