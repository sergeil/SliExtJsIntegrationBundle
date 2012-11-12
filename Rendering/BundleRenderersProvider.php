<?php

namespace Sli\ExtJsIntegrationBundle\Rendering;

use Sli\ExtJsIntegrationBundle\Rendering\RenderersAwareBundle;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Collects all bundles registered in the Kernel which implement
 * {@class RenderersAwareBundle} interface.
 *
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class BundleRenderersProvider implements RenderersProviderInterface
{
    private $kernel;
    private $indexedRenderers;

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;

        $this->init();
    }

    private function init()
    {
        foreach ($this->kernel->getBundles() as $bundle) {
            if ($bundle instanceof RenderersAwareBundle) {
                foreach ($bundle->getRenderers() as $id=>$renderer) {
                    $this->indexedRenderers[$id] = $renderer;
                }
            }
        }
    }

    public function get($id)
    {
        if (!isset($this->indexedRenderers[$id])) {
            throw new \RuntimeException("Renderer with ID '$id' is not found.");
        }

        return $this->indexedRenderers[$id];
    }

    public function has($id)
    {
        return isset($this->indexedRenderers[$id]);
    }
}
