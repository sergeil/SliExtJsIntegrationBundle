<?php

namespace Sli\ExtJsIntegrationBundle;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Sli\ExpanderBundle\DependencyInjection\CompositeContributorsProviderCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel;

class SliExtJsIntegrationBundle extends Bundle
{
    public function boot()
    {
        $reflClass = new \ReflectionClass(__CLASS__);
        $path = dirname($reflClass->getFileName());

        AnnotationRegistry::registerFile(
            $path . '/DataMapping/Annotations.php'
        );
        AnnotationRegistry::registerFile(
            $path . '/Service/SortingFieldAnnotations.php'
        );
    }

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
