<?php

namespace Sli\ExtJsIntegrationBundle\Rendering;

/**
 * If you don't want to use fully-fledged extension-point mechanism provided
 * by {@class Sli\ExpanderBundle\Ext\ContributorInterface}, then you can
 * use Kernel Bundles ( instances of {@class Symfony\Component\HttpKernel\Bundle\BundleInterface} ),
 * to provide renderers.
 *
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
interface RenderersAwareBundle
{
    /**
     * @return array  An array of renderers where "key" is renderer-id and "value",
     *                is callback function
     */
    public function getRenderers();
}