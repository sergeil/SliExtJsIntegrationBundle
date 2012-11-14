<?php

namespace Sli\ExtJsIntegrationBundle\Response;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Implementations will be responsible to making a decision whether
 * {@class JsonResponseEscapingFilter} should be used to escape contents
 * $response.
 *
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
interface EscapingDecisionManagerInterface
{
    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @return boolean  TRUE if for the provided $response escaping must be applied or FALSE to skip it
     */
    public function isEscapingNeeded(Request $request, Response $response);
}
