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
            ->enumNode('persist')
            ->values(array(WizardConfiguration::PERSIST_TYPE_STEP_BY_STEP, WizardConfiguration::PERSIST_TYPE_POST_PRESET))
            ->defaultValue(WizardConfiguration::PERSIST_TYPE_STEP_BY_STEP)
            ->end()
            ->scalarNode('flusher')
            ->defaultValue('@doctrine.orm.entity_manager')
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
            ->scalarNode('condition')
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
