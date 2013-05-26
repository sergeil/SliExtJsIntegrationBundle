<?php

namespace Sli\ExtJsIntegrationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    const EM_BUNDLE = 'bundle';
    const EM_EXPANDER = 'expander';
    const EM_BUNDLE_AND_EXPANDER = 'bundle_and_expander';

    static public $VALID_EXTENSIBILITY_METHODS = array(
        self::EM_BUNDLE, self::EM_EXPANDER, self::EM_BUNDLE_AND_EXPANDER
    );

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sli_ext_js_integration');

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        $rootNode
            ->children()
                ->scalarNode('extensibility_method')
                    ->defaultValue('bundle')
                    ->validate()
                        ->ifNotInArray(self::$VALID_EXTENSIBILITY_METHODS)
                        ->then(function() {
                            $e = new InvalidConfigurationException('Invalid value for "extensibility_mechanism" is given');
                            $e->setPath('sli_ext_js_integration/extensibility_mechanism');
                            throw $e;
                        })
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
