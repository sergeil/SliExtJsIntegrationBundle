<?php

namespace Sli\ExtJsIntegrationBundle\Response;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Abstract class to simplify writing decision-manager for ExtjsDirect requests
 * ( http://www.sencha.com/products/extjs/extdirect )
 *
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
abstract class AbstractExdDecisionManager implements EscapingDecisionManagerInterface
{
    /**
     * Will attempt to guess if the provided $request was sent by ExtjsDirect
     * compatible runtime on client-side.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return bool
     */
    protected function isDirectRequest(Request $request)
    {
        $requestContent = $this->decodeRequest($request);
        if (is_array($requestContent)) {
            foreach (array('action', 'method', 'data', 'type', 'tid') as $paramName) {
                if (!isset($requestContent[$paramName])) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function decodeRequest(Request $request)
    {
        return json_decode($request->getContent(), true);
    }
}
