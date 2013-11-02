<?php

namespace Sli\ExtJsIntegrationBundle\QueryBuilder;

use Doctrine\ORM\QueryBuilder;

/**
 * @internal
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class DoctrineQueryBuilderParametersBinder 
{
    private $qb;
    private $values = array();

    public function __construct(QueryBuilder $qb)
    {
        $this->qb = $qb;
    }

    /**
     * @param mixed $value
     *
     * @return int
     */
    public function bind($value)
    {
        $this->values[] = $value;

        return $this->getNextIndex();
    }

    public function getNextIndex()
    {
        return count($this->values);
    }

    public function injectParameters()
    {
        $this->qb->setParameters($this->values);
    }
}