<?php

namespace Sli\ExtJsIntegrationBundle;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Sli\ExpanderBundle\DependencyInjection\CompositeContributorsProviderCompilerPass;
use Sli\ExpanderBundle\Ext\ExtensionPoint;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel;

SliExtJsIntegrationBundle::loadAnnotations();

class SliExtJsIntegrationBundle extends Bundle
{
    static public function loadAnnotations()
    {
        $reflClass = new \ReflectionClass(__CLASS__);
        $path = dirname($reflClass->getFileName());

        // Starting from `doctrine/annotations 2.0` annotations will be autoloaded
        if (method_exists(AnnotationRegistry::class, 'registerFile')) {
            AnnotationRegistry::registerFile(
                $path . '/DataMapping/Annotations.php'
            );
            AnnotationRegistry::registerFile(
                $path . '/Service/SortingFieldAnnotations.php'
            );
        }
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

        $valueConverterProviders = new ExtensionPoint('sli.extjsintegration.complex_field_value_converters');
        $valueConverterProviders->setDescription('Allows to contribute custom value converters');
        $container->addCompilerPass($valueConverterProviders->createCompilerPass());
    }
}
