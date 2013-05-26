<?php

namespace Sli\ExtJsIntegrationBundle\Rendering;

use Sli\ExpanderBundle\Ext\ContributorInterface;

/**
 * Provides integration layer with SliExpanderBundle and its extension-points
 * architecture.
 *
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class ExpanderRenderersProvider implements RenderersProviderInterface
{
    private $provider;

    /**
     * @return ContributorInterface
     */
    public function getProvider()
    {
        return $this->provider;
    }

    public function __construct(ContributorInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @inheritDoc
     */
    public function has($id)
    {
        $items = $this->provider->getItems();
        return isset($items[$id]);
    }

    /**
     * @inheritDoc
     */
    public function get($id)
    {
        $items = $this->provider->getItems();
        return isset($items[$id]) ? $items[$id] : null;
    }

    static public function clazz()
    {
        return get_called_class();
    }
}
