<?php

namespace Sli\ExtJsIntegrationBundle\Service;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
interface SortingFieldResolverInterface
{
    /**
     * @param string $entityFqcn
     * @param string $fieldName
     */
    public function resolve($entityFqcn, $fieldName);
}
