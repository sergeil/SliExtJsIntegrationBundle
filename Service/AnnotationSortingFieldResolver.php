<?php

namespace Sli\ExtJsIntegrationBundle\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Sli\AuxBundle\Util\Toolkit as Tk;
use Sli\ExtJsIntegrationBundle\Util\EntityManagerResolver;
use Doctrine\Persistence\ManagerRegistry;

require_once __DIR__.'/SortingFieldAnnotations.php';

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class AnnotationSortingFieldResolver implements SortingFieldResolverInterface
{
    /**
     * @since 1.1.0
     *
     * @var ManagerRegistry
     */
    private $doctrineRegistry;
    private $ar;
    private $defaultPropertyName;

    /**
     * Beware! Constructor's signature has been slightly changed in v1.1.0, so it if you have overridden this
     * method is subclasses then you need to change its signature as well. First argument used to accept instance
     * of EntityManager now it has been changed to ManagerRegistry.
     *
     * @param ManagerRegistry $doctrineRegistry
     * @param string $defaultPropertyName
     * @param AnnotationReader|null $ar
     */
    public function __construct(ManagerRegistry $doctrineRegistry, $defaultPropertyName = 'id', AnnotationReader $ar = null)
    {
        $this->doctrineRegistry = $doctrineRegistry;

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
        $metadata = $this->doctrineRegistry->getManagerForClass($entityFqcn)->getClassMetadata($entityFqcn);
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
