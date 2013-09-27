<?php

namespace Sli\ExtJsIntegrationBundle\DataMapping;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
interface MethodInvocationParametersProviderInterface
{
    public function getParameters($fqcn, $methodName);
}
