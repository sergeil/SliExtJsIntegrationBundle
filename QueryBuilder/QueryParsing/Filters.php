<?php

namespace Sli\ExtJsIntegrationBundle\QueryBuilder\QueryParsing;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class Filters implements \Iterator
{
    private $filters;

    private $iterableFilters = array();
    private $iteratorIndex = 0;

    public function __construct(array $filters)
    {
        foreach ($filters as $rawFilter) {
            $this->addFilter(new Filter($rawFilter));
        }
    }

    /**
     * @param string $property
     *
     * @return null|Filter[]
     */
    public function findByProperty($property)
    {
        return isset($this->filters[$property]) ? $this->filters[$property] : array();
    }

    /**
     * @throws \RuntimeException
     * @param string $property
     * @return Filter|null
     */
    public function findOneByProperty($property)
    {
        if (isset($this->filters[$property])) {
            if (count($this->filters[$property]) > 1) {
                throw new \RuntimeException(sprintf(
                    "It was expected that property '%s' would have only one filter defined for it, but in fact it has %d.",
                    $property, count($this->filters[$property])
                ));
            }

            return $this->filters[$property][0];
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
        if ($filter->isValid()) {
            if (!isset($this->filters[$filter->getProperty()])) {
                $this->filters[$filter->getProperty()] = array();
            }

            $this->filters[$filter->getProperty()][] = $filter;
            $this->iterableFilters[] = $filter;

            return true;
        }

        return false;
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
        if (isset($this->filters[$filter->getProperty()])) {
            $siftedResult = array();
            foreach ($this->filters[$filter->getProperty()] as $currentFilter) {
                if ($currentFilter === $filter) {
                    continue;
                }

                $siftedResult[] = $currentFilter;
            }

            $this->filters[$filter->getProperty()] = $siftedResult;

            // dealing with iterator

            $siftedResult = array();
            foreach ($this->iterableFilters as $currentFilter) {
                if ($currentFilter === $filter) {
                    continue;
                }

                $siftedResult[] = $currentFilter;
            }

            $this->iterableFilters = $siftedResult;

            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public function compile()
    {
        $result = array();

        foreach ($this->filters as $propertyFilters) {
            foreach ($propertyFilters as $filter) {
                /* @var Filter $filter */
                $result[] = $filter->compile();
            }
        }

        return $result;
    }

    // iterator:

    public function current()
    {
        return $this->iterableFilters[$this->iteratorIndex];
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
        return isset($this->iterableFilters[$this->iteratorIndex]);
    }

    public function rewind()
    {
        $this->iteratorIndex = 0;
        reset($this->iterableFilters);
    }
}