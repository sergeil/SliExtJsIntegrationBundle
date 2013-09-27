<?php

namespace Sli\ExtJsIntegrationBundle\QueryBuilder\ResolvingAssociatedModelSortingField;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class MutableFieldResolver implements SortingFieldResolverInterface
{
    private $mapping = array();

    /**
     * @param string $entityFqcn
     * @param string $fieldName
     * @param string $result
     */
    public function add($entityFqcn, $fieldName, $result)
    {
        if (!isset($this->mapping[$entityFqcn])) {
            $this->mapping[$entityFqcn] = array();
        }

        $this->mapping[$entityFqcn][$fieldName] = $result;
    }

    /**
     * @param string $entityFqcn
     * @param string $fieldName
     *
     * @return string
     */
    public function resolve($entityFqcn, $fieldName)
    {
        return   isset($this->mapping[$entityFqcn]) && isset($this->mapping[$entityFqcn][$fieldName])
               ? $this->mapping[$entityFqcn][$fieldName]
               : null;
    }
}