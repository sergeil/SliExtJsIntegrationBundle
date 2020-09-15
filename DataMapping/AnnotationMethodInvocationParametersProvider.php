<?php

namespace Sli\ExtJsIntegrationBundle\DataMapping;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Sli\ExtJsIntegrationBundle\DataMapping\Params as ParamsAnn;

require_once __DIR__ . '/Annotations.php';

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class AnnotationMethodInvocationParametersProvider implements MethodInvocationParametersProviderInterface
{
    private $ar;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->ar = new AnnotationReader();
    }

    public function getParameters($fqcn, $methodName)
    {
        try {
            return $this->doGetParameters($fqcn, $methodName);
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "Unable to properly handle DataMapping\\Params annotation on $fqcn::$methodName.", null, $e
            );
        }
    }

    protected function doGetParameters($fqcn, $methodName)
    {
        /* @var ParamsAnn $ann */
        $ann = $this->ar->getMethodAnnotation(new \ReflectionMethod($fqcn, $methodName), ParamsAnn::clazz());
        if ($ann) {
            if (!is_array($ann->value)) {
                throw new \RuntimeException('Value of the annotation must always be an array!');
            }

            $result = array();
            foreach ($ann->value as $serviceName) {
                if ($serviceName[strlen($serviceName)-1] == '*') { // optional service
                    $result[] = $this->container->get($serviceName, ContainerInterface::NULL_ON_INVALID_REFERENCE);
                } else {
                    $result[] = $this->container->get($serviceName, ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE);
                }
            }
            return $result;
        } else {
            return array();
        }
    }
}
