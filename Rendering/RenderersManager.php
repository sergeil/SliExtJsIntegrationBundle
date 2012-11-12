<?php

namespace Sli\ExtJsIntegrationBundle\Rendering;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides couple methods that simplify rendering routine.
 *
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class RenderersManager
{
    private $provider;
    private $container;

    public function __construct(RenderersProviderInterface $provider, ContainerInterface $container)
    {
        $this->provider = $provider;
        $this->container = $container;
    }

    public function render($rendererId, $value, $fieldName = null, $object = null)
    {
        $renderer = $this->provider->get($rendererId);
        return $renderer($value, $fieldName, $object, $this->container);
    }

    public function renderSafely($rendererId, $value, $fieldName = null, $object = null)
    {
        if (!$this->provider->has($rendererId)) {
            return "[renderer '$rendererId' is not found]";
        } else {
            try {
                return $this->render($rendererId, $value, $fieldName, $object);
            } catch (\Exception $e) {
                return $rendererId.' error: '.$e->getMessage();
            }
        }
    }

    static public function clazz()
    {
        return get_called_class();
    }
}
