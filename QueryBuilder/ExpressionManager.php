<?php

namespace Sli\ExtJsIntegrationBundle\QueryBuilder;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Sli\ExtJsIntegrationBundle\QueryBuilder\Parsing\Expression;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class ExpressionManager
{
    private $fqcn;
    private $em;

    private $rootAlias;
    private $allocatedAliases = array();
    private $validatedExpressions = array();

    public function __construct($fqcn, EntityManager $em, $rootAlias = 'e')
    {
        $this->fqcn = $fqcn;
        $this->em = $em;
        $this->rootAlias = $rootAlias;
    }

    /**
     * Don't use nor rely on this method existence!
     *
     * @internal
     * @return array
     */
    public function getAllocatedAliasMap()
    {
        return $this->allocatedAliases;
    }

    /**
     * @return string
     */
    public function getRootAlias()
    {
        return $this->rootAlias;
    }

    /**
     * @param string $expression
     */
    public function isValidExpression($expression)
    {
        if (!isset($this->validatedExpressions[$expression])) {
            $this->validatedExpressions[$expression] = $this->doIsValidExpression($expression);
        }

        return $this->validatedExpressions[$expression];
    }

    protected function doIsValidExpression($expression)
    {
        if (strpos($expression, '.') !== false) { //
            $parsed = explode('.', $expression);
            $fqcn = $this->fqcn;
            foreach ($parsed as $index=>$propertyName) {
                $meta = $this->em->getClassMetadata($fqcn);
                if ($meta->hasAssociation($propertyName)) {
                    $mapping = $meta->getAssociationMapping($propertyName);
                    $fqcn = $mapping['targetEntity'];

                    if ((count($parsed)-1)  == $index) { // association is the last segment
                        return true;
                    }
                } else if ($meta->hasField($propertyName)) {
                    return true;
                }
            }
            return false;
        } else {
            $meta = $this->em->getClassMetadata($this->fqcn);
            return $meta->hasField($expression) || $meta->hasAssociation($expression);
        }
    }

    private function validateExpression($expression)
    {
        if (!$this->isValidExpression($expression)) {
            throw new \RuntimeException("'$expression' doesn't look to be a valid expression for entity {$this->fqcn}.");
        }
    }

    /**
     * @throws \RuntimeException
     *
     * @param string $expression
     *
     * @return string  Alias to given $expression
     */
    public function allocateAlias($expression)
    {
        $parsedExpression = explode('.', $expression);

        $meta = $this->em->getClassMetadata($this->fqcn);
        foreach ($parsedExpression as $index=>$propertyName) {
            if (!$meta->hasAssociation($propertyName)) {
                throw new \RuntimeException(sprintf(
                    "Error during parsing of '$expression' expression. Entity '%s' doesn't have association '%s'.",
                    $meta->getName(), $propertyName
                ));
            }

            $mapping = $meta->getAssociationMapping($propertyName);
            $meta = $this->em->getClassMetadata($mapping['targetEntity']);

            $currentExpression = implode('.', array_slice($parsedExpression, 0, $index+1));
            if (!$this->resolveExpressionToAlias($expression) && !$this->resolveExpressionToAlias($currentExpression)) {
                $this->doAllocateAlias($currentExpression);
            }
        }

        return $this->resolveExpressionToAlias($expression);
    }

    /**
     * Allocates a DQL join alias for a given $expression
     *
     * @param string $expression
     *
     * @return string
     */
    private function doAllocateAlias($expression)
    {
        $alias = 'j' . count($this->allocatedAliases);
        $this->allocatedAliases[$alias] = $expression;

        return $alias;
    }

    /**
     * @param string $alias
     *
     * @return string|null Expression for the provided $alias, if $alias is not found, NULL is returned
     */
    public function resolveAliasToExpression($alias)
    {
        return isset($this->allocatedAliases[$alias]) ? $this->allocatedAliases[$alias] : null;
    }

    /**
     * @param string $expression
     *
     * @return string|false  Alias for a given $expression. If expression is not found, then FALSE is returned
     */
    public function resolveExpressionToAlias($expression)
    {
        return array_search($expression, $this->allocatedAliases);
    }

    /**
     * @throws \RuntimeException
     *
     * @param string $expression
     *
     * @return string For a given $expression, will return a correct variable name with alias that you
     *                can use in your DQL query
     */
    public function getDqlPropertyName($expression)
    {
        $this->validateExpression($expression);

        if (strpos($expression, '.') !== false) { // associative expression
            $parsedExpression = explode('.', $expression);
            $propertyName = array_pop($parsedExpression);

            return $this->allocateAlias(implode('.', $parsedExpression)) . '.' . $propertyName;
        } else {
            return $this->getRootAlias() . '.' . $expression;
        }
    }

    /**
     * @param string $expression
     *
     * @return array
     */
    private function expandExpression($expression)
    {
        $result = array();

        $explodedExpression = explode('.', $expression);
        foreach ($explodedExpression as $i=>$segment) {
            $result[] = implode('.', array_slice($explodedExpression, 0, $i+1));
        }

        return $result;
    }

    /**
     * @param QueryBuilder $qb
     * @param array $expressions
     */
    private function doInjectJoins(QueryBuilder $qb, array $expressions)
    {
        foreach (array_values($expressions) as $i=>$expression) {
            $alias = $this->resolveExpressionToAlias($expression);

            $parsedExpression = explode('.', $expression);

            if (0 == $i) {
                $qb->leftJoin($this->rootAlias . '.' . $expression, $alias);
            } else if (count($parsedExpression) == 1) {
                $qb->leftJoin($this->rootAlias . '.' . $parsedExpression[0], $alias);
            } else {
                $rootExpression = implode('.', array_slice($parsedExpression, 0, -1));
                $propertyName = end($parsedExpression);

                $parentAlias = $this->resolveExpressionToAlias($rootExpression);
                $qb->leftJoin($parentAlias . '.' . $propertyName, $alias);
            }
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param bool $useFetchJoins  If provided then joined entities will be fetched as well
     */
    public function injectJoins(QueryBuilder $qb, $useFetchJoins = true)
    {
        if ($useFetchJoins) {
            $expressions = array();
            foreach ($this->allocatedAliases as $rawExpression) {
                $expressions[] = new Expression($rawExpression);
            }

            $this->injectFetchSelects($qb, $expressions);
        } else {
            $this->doInjectJoins($qb, $this->allocatedAliases);
        }
    }

    /**
     * When selects are injected then apparently the joins will be added to the query as well, so you either
     * use this method or injectJoins() but not both of them at the same time.
     *
     * @param QueryBuilder $qb
     * @param Expression[] $expressions  All expressions which were provided in "fetch". The method will filter
     *                                   "select" fetches by itself
     */
    public function injectFetchSelects(QueryBuilder $qb, array $expressions)
    {
        $expandedExpressions = array();
        foreach ($expressions as $expression) {
            $isFetchOnly = !$expression->getAlias() && !$expression->getFunction();

            // we need to have only "fetch" expressions
            if ($isFetchOnly && $this->isAssociation($expression->getExpression())) {
                $expandedExpressions = array_merge($expandedExpressions, $this->expandExpression($expression->getExpression()));
            }
        }

        $expandedExpressions = array_values(array_unique($expandedExpressions));

        $selects = array();
        foreach ($qb->getDQLPart('select') as $select) {
            /* @var \Doctrine\ORM\Query\Expr\Select $select */
            $selects[] = trim((string)$select);
        }

        $map = array();
        foreach ($expandedExpressions as $expression) {
            $this->allocateAlias($expression);

            $map[$this->resolveExpressionToAlias($expression)] = $expression;
        }

        foreach ($map as $alias=>$expression) {
            if (!in_array($alias, $selects)) {
                $qb->addSelect($alias);
            }
        }

        $this->doInjectJoins($qb, $expandedExpressions);
    }

    /**
     * @throws \RuntimeException
     *
     * @param string $expression
     *
     * @return array  Doctrine field's mapping
     */
    public function getMapping($expression)
    {
        $this->validateExpression($expression);

        $meta = $this->em->getClassMetadata($this->fqcn);
        $parsedExpression = explode('.', $expression);
        foreach ($parsedExpression as $index=>$propertyName) {
            $mapping = $meta->hasAssociation($propertyName)
                     ? $meta->getAssociationMapping($propertyName)
                     : $meta->getFieldMapping($propertyName);

            if ($meta->hasAssociation($propertyName)) {
                $meta = $this->em->getClassMetadata($mapping['targetEntity']);
            }

            if ((count($parsedExpression)-1) == $index) {
                return $mapping;
            }
        }
    }

    /**
     * @param string $expression
     *
     * @return bool
     */
    public function isAssociation($expression)
    {
        $this->validateExpression($expression);

        $meta = $this->em->getClassMetadata($this->fqcn);
        $parsedExpression = explode('.', $expression);
        foreach ($parsedExpression as $index=>$propertyName) {
            $mapping = $meta->hasAssociation($propertyName)
                     ? $meta->getAssociationMapping($propertyName)
                     : $meta->getFieldMapping($propertyName);

            if ($meta->hasAssociation($propertyName)) {
                if ((count($parsedExpression)-1) == $index) {
                    return true;
                }

                $meta = $this->em->getClassMetadata($mapping['targetEntity']);
            }
        }

        return false;
    }
}
