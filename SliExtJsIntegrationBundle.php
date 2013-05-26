<?php

namespace Sli\ExtJsIntegrationBundle;

use Sli\ExpanderBundle\DependencyInjection\CompositeContributorsProviderCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel;

class SliExtJsIntegrationBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        // FIXME ain't good, it must be done rather in RendererProvidersConfigurator
        $container->addCompilerPass(
            new CompositeContributorsProviderCompilerPass(
                'sli.extjsintegration.expander_renderers_provider',
                'sli.extjsintegration.renderers_provider'
            )
        );
    }

}
