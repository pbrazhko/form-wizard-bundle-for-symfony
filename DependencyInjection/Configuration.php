<?php

namespace CMS\FormWizardBundle\DependencyInjection;

use CMS\FormWizardBundle\WizardConfiguration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('form_wizard');

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        $rootNode
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->children()
            ->scalarNode('entity_manager')
                            ->defaultValue('@doctrine.orm.entity_manager')
                        ->end()
                        ->arrayNode('steps')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                            ->children()
                                ->scalarNode('type')->isRequired()->end()
                                ->scalarNode('condition')
            ->defaultValue(null)
            ->end()
            ->scalarNode('template')
                                    ->defaultValue(null)
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
