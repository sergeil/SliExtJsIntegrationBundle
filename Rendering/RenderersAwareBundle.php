<?php

namespace Sli\ExtJsIntegrationBundle\Rendering;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
interface RenderersAwareBundle
{
    /**
     * @return array
     */
    public function getRenderers();
}