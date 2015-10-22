<?php

namespace CMS\FormWizardBundle\DependencyInjection;

use CMS\FormWizardBundle\Wizard;
use CMS\FormWizardBundle\WizardConfiguration;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class FormWizardExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (count($config)) {
            $this->createWizards($container, $config);
        }

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }

    private function createWizards(ContainerBuilder $container, array $wizards)
    {
        foreach ($wizards as $wizard => $properties) {
            $wizardConfigurationDefinition = new Definition(WizardConfiguration::class, [$properties['steps'], $properties['persist']]);
            $wizardConfigurationDefinition->setPublic(false);

            $wizardDefinition = new Definition(Wizard::class, [$wizardConfigurationDefinition]);
            $wizardDefinition->addMethodCall('setFormBuilder', [new Reference('form.factory')]);

            $container->setDefinition('cms.form_wizard.' . $wizard, $wizardDefinition);
        }
    }
}
