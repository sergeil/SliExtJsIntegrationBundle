<?php

namespace Sli\ExtJsIntegrationBundle\Service;

use Doctrine\ORM\EntityManager;

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
        if (!$this->isValidExpression($expression)) {
            throw new \RuntimeException("'$expression' doesn't seem to be a valid expression for {$this->fqcn}!");
        }

        $alias = array_search($expression, $this->allocatedAliases);
        if ($alias) {
            return $alias;
        }

        return $this->doAllocateAlias($expression);
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
    public function resolveAlias($alias)
    {
        return isset($this->allocatedAliases[$alias]) ? $this->allocatedAliases[$alias] : null;
    }

    /**
     * @param $expression
     * @return string|false  Alias for a given $expression, if expression is not found, then FALSE is returned
     */
    public function resolveExpression($expression)
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
            $parsed = explode('.', $expression);
            $fqcn = $this->fqcn;
            foreach ($parsed as $index=>$propertyName) {
                $meta = $this->em->getClassMetadata($fqcn);
                $currentExpression = implode('.', array_slice($parsed, 0, $index+1));

                if ($meta->hasAssociation($propertyName)) {
                    if ((count($parsed)-1) == $index) {
                        throw new \RuntimeException("You can't use associations in queries but their scalars!");
                    }
                    $mapping = $meta->getAssociationMapping($propertyName);
                    $fqcn = $mapping['targetEntity'];

                    $this->allocateAlias($currentExpression);
                } else {
                    $currentExpression = implode('.', array_slice($parsed, 0, $index));
                    $alias = $this->resolveExpression($currentExpression);
                    return $alias.'.'.$propertyName;
                }
            }
        } else {
            return $this->getRootAlias().'.'.$expression;
        }
    }
}
