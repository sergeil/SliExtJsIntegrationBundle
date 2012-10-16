<?php

namespace Sli\ExtJsIntegrationBundle\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class Model
{
    private $fqcn;
    private $em;

    private $rootAlias;
    public $allocatedAliases = array();

    public function __construct($fqcn, EntityManager $em, $rootAlias = 'e')
    {
        $this->fqcn = $fqcn;
        $this->em = $em;
        $this->rootAlias = $rootAlias;
    }

    public function getRootAlias()
    {
        return $this->rootAlias;
    }

    /**
     * @param string $expression
     */
    public function isValidExpression($expression)
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
            if (!$this->resolveExpressionToAlias($expression)) {
                $this->doAllocateAlias($currentExpression);
            }
        }

        return $this->resolveExpressionToAlias($expression);
    }

    protected function doAllocateAlias($expression)
    {
        $alias = 'j'.count($this->allocatedAliases);
        $this->allocatedAliases[$alias] = $expression;
        return $alias;
    }

    /**
     * @param string $alias
     * @return string|null Expression for the provided $alias, if $alias is not found, NULL is returned
     */
    public function resolveAliasToExpression($alias)
    {
        return isset($this->allocatedAliases[$alias]) ? $this->allocatedAliases[$alias] : null;
    }

    /**
     * @param $expression
     * @return string|false  Alias for a given $expression, if expression is not found, then FALSE is returned
     */
    public function resolveExpressionToAlias($expression)
    {
        return array_search($expression, $this->allocatedAliases);
    }

    /**
     * @throws \RuntimeException
     * @param string $expression  Last segment of expression must not be a relation
     * @return string For a given $expression, will return a correct variable name with alias that you
     *                can you in your DQL
     */
    public function getDqlPropertyName($expression)
    {
        if (!$this->isValidExpression($expression)) {
            throw new \RuntimeException("'$expression' doesn't seem to be a valid expression for {$this->fqcn}!");
        }

        if (strpos($expression, '.') !== false) { // associative expression
            $parsedExpression = explode('.', $expression);
            $propertyName = array_pop($parsedExpression);
            return $this->allocateAlias(implode('.', $parsedExpression)).'.'.$propertyName;
        } else {
            return $this->getRootAlias().'.'.$expression;
        }
    }

    public function injectJoins(QueryBuilder $qb, $injectSelects = true)
    {
        $i=0;
        foreach ($this->allocatedAliases as $alias=>$expression) {
            $parsedExpression = explode('.', $expression);
            if (0 == $i) {
                $qb->leftJoin($this->rootAlias.'.'.$expression, $alias);
            } else if (count($parsedExpression) == 1) {
                $qb->leftJoin($this->rootAlias.'.'.$parsedExpression[0], $alias);
            } else {
                $previousAlias = array_keys($this->allocatedAliases);
                $previousAlias = $previousAlias[$i-1];
                $qb->leftJoin($previousAlias.'.'.$parsedExpression[count($parsedExpression)-1], $alias);
            }
            $i++;
        }

        if ($injectSelects) {
            $selects = array();
            foreach ($qb->getDQLPart('select') as $select) {
                /* @var \Doctrine\ORM\Query\Expr\Select $select */
                $selects[] = trim($select->__toString());
            }
            foreach ($this->allocatedAliases as $alias=>$expression) {
                if (!in_array($alias, $selects)) {
                    $qb->addSelect($alias);
                }
            }
        }
    }
}
