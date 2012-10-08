<?php

namespace Sli\ExtJsIntegrationBundle\Service\DataMapping;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
interface MethodInvocationParametersProviderInterface
{
    public function getParameters($fqcn, $methodName);
}
