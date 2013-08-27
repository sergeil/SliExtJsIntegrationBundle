<?php

namespace Sli\ExtJsIntegrationBundle\DataMapping;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */ 
interface ComplexFieldValueConverterInterface
{
    /**
     * @param string $value
     * @param string $fieldName
     * @param ClassMetadataInfo $meta
     * @return boolean
     */
    public function isResponsible($value, $fieldName, ClassMetadataInfo $meta);

    /**
     * @param string $fieldValue
     * @param string $fieldName
     * @param ClassMetadataInfo $meta
     *
     * @return mixed
     */
    public function convert($fieldValue, $fieldName, ClassMetadataInfo $meta);
}
