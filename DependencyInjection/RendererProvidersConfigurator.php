<?php

namespace Sli\ExtJsIntegrationBundle\DependencyInjection;

use Sli\ExpanderBundle\DependencyInjection\CompositeContributorsProviderCompilerPass;
use Sli\ExtJsIntegrationBundle\Rendering\BundleRenderersProvider;
use Sli\ExtJsIntegrationBundle\Rendering\ExpanderRenderersProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class RendererProvidersConfigurator
{
    public function configure(array $config, ContainerBuilder $container)
    {
        $rendererDefinition = null;
        switch ($config['extensibility_method']) {
            case Configuration::EM_BUNDLE:
                $rendererDefinition = $this->configureBundle($container);
                break;

            case Configuration::EM_BUNDLE_AND_EXPANDER:
                $rendererDefinition = $this->configureExpander($container);

                // by tagging a service we are making it visible to CompositeContributorsProviderCompilerPass
                $bundleRendererDefinition = $this->configureBundle($container);
                $bundleRendererDefinition->addTag('sli.extjsintegration.renderers_provider');

                $container->setDefinition(
                    'sli.extjsintegration.bundle_renderers_provider',
                    $bundleRendererDefinition
                );

                break;

            case Configuration::EM_EXPANDER:
                $rendererDefinition = $this->configureExpander($container);
                break;
        }

        $container->setDefinition(
            'sli.extjsintegration.renderers_provider',
            $rendererDefinition
        );
    }

    private function configureBundle(ContainerBuilder $container)
    {
        return new Definition(
            BundleRenderersProvider::clazz(), array(new Reference('kernel'))
        );
    }

    private function configureExpander(ContainerBuilder $container)
    {
        $providerId = 'sli.extjsintegration.expander_renderers_provider';

        $container->addCompilerPass(new CompositeContributorsProviderCompilerPass($providerId));
        return new Definition(ExpanderRenderersProvider::clazz(), array(new Reference($providerId)));
    }
}
