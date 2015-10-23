<?php

namespace CMS\FormWizardBundle\DependencyInjection;

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
            ->enumNode('persist')
            ->values(array('stepByStep', 'postPreset'))
            ->defaultValue('stepByStep')
            ->end()
            ->arrayNode('steps')
            ->useAttributeAsKey('name')
            ->prototype('array')
            ->children()
            ->enumNode('method')
            ->values(array('GET', 'POST'))
            ->defaultValue('POST')
            ->end()
            ->scalarNode('type')->isRequired()->end()
            ->scalarNode('condition')->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end();

        return $treeBuilder;
    }
}
