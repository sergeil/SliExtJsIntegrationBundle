<?php

namespace Sli\ExtJsIntegrationBundle\QueryBuilder\Parsing;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class OrFilter implements FilterInterface
{
    private $input;
    private $filters;
    private $isValid = null;

    public function __construct(array $input)
    {
        $this->input = $input;
    }

    /**
     * @return Filter[]
     */
    public function getFilters()
    {
        if (!$this->filters) {
            $this->filters = array();

            foreach ($this->input as $rawFilter) {
                $this->filters[] = new Filter($rawFilter);
            }
        }

        return $this->filters;
    }

    /**
     * @return bool  Will return TRUE only if all aggregated filters are valid
     */
    public function isValid()
    {
        if (null === $this->isValid) {
            $this->isValid = true;
            foreach ($this->getFilters() as $filter) {
                if (!$filter->isValid()) {
                    $this->isValid = false;
                }
            }
        }

        return $this->isValid;
    }

    /**
     * @return array  Only valid filters will be compiled
     */
    public function compile()
    {
        $result = array();
        foreach ($this->getFilters() as $filter) {
            if ($filter->isValid()) {
                $result[] = $filter->compile();
            }
        }

        return $result;
    }

    static public function clazz()
    {
        return get_called_class();
    }
}