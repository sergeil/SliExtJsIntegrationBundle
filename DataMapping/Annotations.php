<?php

namespace Sli\ExtJsIntegrationBundle\DataMapping;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
final class Params extends Annotation
{
    static public function clazz()
    {
        return get_called_class();
    }
}