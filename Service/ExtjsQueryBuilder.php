<?php

namespace Sli\ExtJsIntegrationBundle\Service;

use Doctrine\ORM\EntityManager;
use Sli\ExtJsIntegrationBundle\Service\DataMapping\EntityDataMapperService;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\QueryBuilder;

/**
 * Class helps to build/execute complex queries according to the instructions sent from the client-side, which
 * in turn are build by Ext.data.Store.
 *
 * Say you have a store that looks similar to this one:
 * var store = Ext.create('Ext.data.Store', {
 *    remoteFilter: true,
 *    remoteSort: true,
 *
 *   fields: [
 *        'id', 'firstname', 'lastname'
 *    ],
 *
 *    proxy: {
 *        type: 'direct',
 *        // we are using ExtDirect here
 *        directFn: Actions.AcmeUsers.list,
 *        reader: {
 *            type: 'json',
 *            root: 'items',
 *            totalProperty: 'total'
 *        }
 *    }
 * });
 *
 * // now you can something like that. Note that you can use all
 *
 * // for filter value you gotta use expression:value formatted string, where 'expression' is a valid method-name of
 * // Doctrine\DBAL\Query\Expression\ExpressionBuilder.
 * // Also, if you bind a store to a grid, then by clicking on a column header ExtJs will automatically
 * // interact with the associated store to send proper sorting request. In order to start using pagination,
 * // take a look at http://docs.sencha.com/ext-js/4-1/#!/api/Ext.toolbar.Paging
 *
 * store.filter('firstname', 'like:John*');
 * store.clearFilter();
 *
 * store.filter('id', 'eq:2');
 * store.clearFilter();
 *
 * store.filter('id', 'notIn:1,2');
 *
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class ExtjsQueryBuilder
{
    private $em;
    private $mapper;
    private $sortingFieldResolver;

    public function __construct(EntityManager $em, EntityDataMapperService $mapper, SortingFieldResolverInterface $sortingFieldResolver)
    {
        $this->em = $em;
        $this->mapper = $mapper;
        $this->sortingFieldResolver = $sortingFieldResolver;
    }

    protected function sanitizeDqlFieldName($name)
    {
        return preg_replace('/^[^a-zA-Z0-9_]*$/', '', $name);
    }

    /**
     * @param array $params
     * @return \Doctrine\ORM\Query
     */
    public function buildQuery($entityFqcn, array $params)
    {
        return $this->buildQueryBuilder($entityFqcn, $params)->getQuery();
    }

    protected function convertValue(ExpressionManager $expressionManager, $expression, $value)
    {
        $mapping = $expressionManager->getMapping($expression);
        return $this->mapper->convertValue($value, $mapping['type']);
    }

    protected function parseValue($value)
    {
        // value should always be "comparator:value", ex: "like:Vasya"
        $pos = strpos($value, ':');
        if (false === $pos) {
            return false;
        }

        return array(substr($value, 0, $pos), substr($value, $pos+1));
    }

    /**
     * @param string $entityFqcn  Root fetch entity fully-qualified-class-name
     * @param array $params  Parameters that were sent from client-side
     * @return \Doctrine\ORM\QueryBuilder
     * @throws \RuntimeException
     */
    public function buildQueryBuilder($entityFqcn, array $params)
    {
        $metadata = $this->em->getClassMetadata($entityFqcn);
        $availableFields = array_merge($metadata->getFieldNames(), $metadata->getAssociationNames());

        $expressionManager = new ExpressionManager($entityFqcn, $this->em);

        $qb = $this->em->createQueryBuilder();
        $expr = $qb->expr();

        $orderStms = array(); // contains ready DQL orderBy statement that later will be joined together
        if (isset($params['sort'])) {
            $orderConds = array(); // sanitized ones
            foreach ($params['sort'] as $entry) { // sanitizing and filtering
                if (!isset($entry['property']) || !isset($entry['direction'])) {
                    continue;
                }

                list($propertyName, $direction) = array_values($entry);
//                if (!in_array($propertyName, $availableFields)) {
//                    continue;
//                }
                if (!$expressionManager->isValidExpression($propertyName)) {
                    continue;
                }

                $propertyName = $this->sanitizeDqlFieldName($propertyName);
                $direction = strtoupper($direction);

                if (!in_array($direction, array('ASC', 'DESC'))) {
                    continue;
                }

                $orderConds[$propertyName] = $direction;
            }

            $indexedAliases = array(); // for association properties only
            $selectParams = array('e'); // 'e' goes for the root entity
            foreach ($orderConds as $propertyName=>$direction) { // generating data required for proper SELECT stmt
                if (in_array($propertyName, $metadata->getAssociationNames())) {
                    $dqlAlias = 'j'.count($orderStms);
                    $selectParams[] = $dqlAlias;
                    $indexedAliases[$propertyName] = $dqlAlias;

                    // data cannot be ordered by an association itself, but by a field
                    // from the associated entity
                    $orderField = $this->sortingFieldResolver->resolve($entityFqcn, $propertyName);
                    $orderStms[] = $dqlAlias.'.'.$orderField.' '.$direction;
                } else {
                    $orderStms[] = 'e.'.$propertyName.' '.$direction;
                }
            }

            $qb->add('select', implode(', ', $selectParams));
            $qb->add('from', $entityFqcn.' e');

            foreach ($orderConds as $propertyName=>$direction) { // adding necessary fetching joins
                if (in_array($propertyName, $metadata->getAssociationNames())) {
                    $qb->leftJoin('e.'.$propertyName, $indexedAliases[$propertyName], 'WITH');
                }
            }
        } else {
            $qb->add('select', 'e');
            $qb->add('from', $entityFqcn.' e');
        }

        if (isset($params['start'])) {
            $start = $params['start'];
            if (isset($params['page']) && isset($params['limit'])) {
                $start = ($params['page']-1) * $params['limit'];
            }
            $qb->setFirstResult($start);
        }
        if (isset($params['limit'])) {
            $qb->setMaxResults($params['limit']);
        }

        if (isset($params['filter'])) {
            $exprMethods = get_class_methods($expr);

            $valuesToBind = array();
            $andExpr = $qb->expr()->andX();
            foreach ($params['filter'] as $filter) {
                if (!isset($filter['property']) || !isset($filter['value'])) {
                    continue;
                }
                $name = $filter['property'];
                $value = $this->parseValue($filter['value']);
                if (false === $value) {
                    continue;
                }

                $comparatorName = $value[0];
                if (!in_array($comparatorName, $exprMethods)) {
                    continue;
                }

                $value = $value[1];
                if (in_array($comparatorName, array('in', 'notIn'))) {
                    $value = explode(',', $value);
                    if (count($value) == 1 && '' == $value[0]) { // there's no point of having IN('')
                        continue;
                    }
                }

                // if this is association field, then sometimes there could be just 'no-value'
                // state which is conventionally marked as '-' value
//                if ($metadata->hasAssociation($name) && '-' === $value) {
//                    continue;
//                }
                if ($expressionManager->isAssociation($name) && '-' === $value) {
                    continue;
                }

                $sanitizedFieldName = $this->sanitizeDqlFieldName($name);
//                if (!$sanitizedFieldName) {
//                    continue;
//                } else if (!in_array($sanitizedFieldName, $availableFields)) {
//                    throw new \RuntimeException(
//                        "There's no field with name '$sanitizedFieldName' found in model '$entityFqcn'."
//                    );
//                }
//
//                $fieldName = 'e.'.$sanitizedFieldName;

                $fieldName = $expressionManager->getDqlPropertyName($name);

                if (in_array($comparatorName, array('isNull', 'isNotNull'))) {
                    $andExpr->add(
                        $qb->expr()->$comparatorName($fieldName)
                    );
                } else {
                    $andExpr->add(
                        $qb->expr()->$comparatorName($fieldName, '?'.count($valuesToBind))
                    );
//                    $valuesToBind[] = $this->convertValue($metadata, $sanitizedFieldName, $value);
                    $valuesToBind[] = $this->convertValue($expressionManager, $name, $value);
                }
            }

            if ($andExpr->count() > 0) {
                $qb->where($andExpr);
                $qb->setParameters($valuesToBind);
            }
        }

        if (isset($params['fetch']) && is_array($params['fetch'])) {
            foreach ($params['fetch'] as $expression) {
                $qb->addSelect($expressionManager->allocateAlias($expression));
            }
        }

        $expressionManager->injectJoins($qb, false);

        if (count($orderStms) > 0) {
            $qb->add('orderBy', implode(', ', $orderStms));
        }

        return $qb;
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param callable $hydrator  An instance of \Closure ( anonymous functions ) that will be used to hydrate fetched
     *                            from database entities. Entity that needs to be hydrated will be passed as a first and
     *                            only argument to the function.
     * @param string|null $rootFetchEntityFqcn  If your fetch query contains several SELECT entries, then you need
          *                                          to specify which entity we must use to build COUNT query with
     * @return array   Response that should be sent back to the client side. You need to have a properly
     *                 configured proxy's reader for your store, it should be of json type with the following config:
     *                 { type: 'json', root: 'items', totalProperty: 'total' }
     */
    public function buildResponseWithPagination(QueryBuilder $qb, \Closure $hydrator, $rootFetchEntityFqcn = null)
    {
        $countQueryBuilder = $this->buildCountQueryBuilder($qb, $rootFetchEntityFqcn);

        $hydratedItems = array();
        foreach ($qb->getQuery()->getResult() as $item) {
            $hydratedItems[] = $hydrator($item);
        }

        return array(
            'success' => true,
            'total' => $countQueryBuilder->getQuery()->getSingleScalarResult(),
            'items' => $hydratedItems
        );
    }

    /**
     * @throws \RuntimeException
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder  Fetch query-builder, in other words - instance of QueryBuilder
     *                                                  that will be used to actually execute SELECT query for response
     *                                                  you are going to send back
     * @param string|null $rootFetchEntityFqcn  If your fetch query contains several SELECT entries, then you need
     *                                          to specify which entity we must use to build COUNT query with
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function buildCountQueryBuilder(QueryBuilder $queryBuilder, $rootFetchEntityFqcn = null)
    {
        $countQueryBuilder = clone $queryBuilder;
        $countQueryBuilder->setFirstResult(null);
        $countQueryBuilder->setMaxResults(null);
        $parts = $countQueryBuilder->getDQLParts();

        if (!isset($parts['select']) || count($parts['select']) == 0) {
            throw new \RuntimeException('Provided $queryBuilder doesn\'t contain SELECT part.');
        }
        if (!isset($parts['from'])) {
            throw new \RuntimeException('Provided $queryBuilder doesn\'t contain FROM part.');
        }
        if (null === $rootFetchEntityFqcn && count($parts['select']) > 1) {
            throw new \RuntimeException(
                'Provided $queryBuilder contains more than fetch entity in its SELECT statement but you haven\'t provided $rootFetchEntityFqcn'
            );
        }

        $rootAlias = null;
        if (count($parts['select']) > 1) {
            foreach ($parts['from'] as $fromPart) {
                list($entityFqcn, $alias) = explode(' ', $fromPart);
                if ($entityFqcn === $rootFetchEntityFqcn) {
                    $rootAlias = $alias;
                }
            }
        } else {
            $rootAlias = $parts['select'][0];

            $isFound = false;
            foreach ($parts['from'] as $fromPart) {
                list($entityFqcn, $alias) = explode(' ', $fromPart);
                if ($alias === $rootAlias) {
                    $isFound = true;
                }
            }
            if (!$isFound) {
                throw new \RuntimeException(
                    "Unable to resolve fetch entity FQCN for alias '$rootAlias'. Do you have your SELECT and FROM parts properly built ?"
                );
            }
        }
        if (null === $rootAlias) {
            throw new \RuntimeException("Unable to resolve alias for entity $rootFetchEntityFqcn");
        }

        $countQueryBuilder->add('select', "COUNT ({$parts['select'][0]})");
        return $countQueryBuilder;
    }

    public function getResult($entityFqcn, array $params)
    {
        return $this->buildQuery($entityFqcn, $params)->getResult();
    }
}
