<?php

namespace Sli\ExtJsIntegrationBundle\QueryBuilder\ResolvingAssociatedModelSortingField;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class ChainSortingFieldResolver implements SortingFieldResolverInterface
{
    /**
     * @var SortingFieldResolverInterface[]
     */
    private $resolvers = array();

    /**
     * @param SortingFieldResolverInterface $resolver
     */
    public function add(SortingFieldResolverInterface $resolver)
    {
        $this->resolvers[spl_object_hash($resolver)] = $resolver;
    }

    /**
     * @return SortingFieldResolverInterface[]
     */
    public function all()
    {
        return array_values($this->resolvers);
    }

    /**
     * @inheritDoc
     */
    public function resolve($entityFqcn, $fieldName)
    {
        foreach ($this->resolvers as $resolver) {
            $result = $resolver->resolve($entityFqcn, $fieldName);
            if (null !== $result) {
                return $result;
            }
        }

        return null;
    }
}