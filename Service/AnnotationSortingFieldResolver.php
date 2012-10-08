<?php

namespace Sli\ExtJsIntegrationBundle\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Annotations\AnnotationReader;
use Sli\AuxBundle\Util\Toolkit as Tk;

require_once __DIR__.'/SortingFieldAnnotations.php';

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class AnnotationSortingFieldResolver implements SortingFieldResolverInterface
{
    private $em;
    private $ar;
    private $defaultPropertyName;

    public function __construct(EntityManager $em, $defaultPropertyName = 'id', AnnotationReader $ar = null)
    {
        $this->em = $em;

        $this->ar = $ar;
        if (null === $ar) {
            $this->ar = new AnnotationReader();
        }

        $this->defaultPropertyName = $defaultPropertyName;
    }

    private function getDefaultPropertyName($entityFqcn)
    {
        $names = array();
        foreach (Tk::getReflectionProperties($entityFqcn) as $reflProperty) {
            /* @var \ReflectionProperty $reflProperty */
            $names[] = $reflProperty->getName();
        }
        if (!in_array($this->defaultPropertyName, $names)) {
            throw new \RuntimeException("$entityFqcn::{$this->defaultPropertyName} doesn't exist.");
        }

        return $this->defaultPropertyName;
    }

    public function resolve($entityFqcn, $fieldName)
    {
        $metadata = $this->em->getClassMetadata($entityFqcn);
        if (!$metadata) {
            throw new \RuntimeException("Unable to load metadata for class '$entityFqcn'.");
        }

        $fieldMapping = $metadata->getAssociationMapping($fieldName);

        /* @var QueryOrder $ann */
        $ann = $this->ar->getPropertyAnnotation(Tk::getReflectionProperty($entityFqcn, $fieldName), QueryOrder::clazz());
        if (!$ann) { // no property annotation found
            $ann = $this->ar->getClassAnnotation(new \ReflectionClass($fieldMapping['targetEntity']), QueryOrder::clazz());
            if (!$ann) { // no class annotation found
                return $this->getDefaultPropertyName($fieldMapping['targetEntity']);
            } else {
                return $ann->value;
            }
        } else {
            return $ann->value;
        }
    }
}
