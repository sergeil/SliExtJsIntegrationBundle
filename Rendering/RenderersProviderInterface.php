<?php

namespace Sli\ExtJsIntegrationBundle\Rendering;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
interface RenderersProviderInterface
{
    /**
     * @param mixed $id
     * @return boolean
     */
    public function has($id);

    /**
     * @param mixed $id
     * @return \Closure
     */
    public function get($id);
}