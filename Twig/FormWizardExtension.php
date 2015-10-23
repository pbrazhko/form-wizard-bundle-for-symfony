<?php

namespace CMS\FormWizardBundle\Twig;

use CMS\FormWizardBundle\Exception\InvalidArgumentException;
use CMS\FormWizardBundle\Wizard;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Twig_Environment;

class FormWizardExtension extends \Twig_Extension
{
    private $env;

    /**
     * @var  ContainerInterface
     */
    private $container;

    /**
     * FormWizardExtension constructor.
     * @param $container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function initRuntime(Twig_Environment $environment)
    {
        $this->env = $environment;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('form_wizard', [$this, 'formWizard'], ['is_safe' => ['html']])
        ];
    }

    public function formWizard($wizardName, $step = null, $template = 'FormWizardBundle:Twig:wizard.html.twig')
    {
        if (!$this->container->has('cms.form_wizard.' . $wizardName)) {
            throw new InvalidArgumentException(sprintf('Wizard %s not found!', $wizardName));
        }

        /**
         * @var Wizard $wizard
         */
        $wizard = $this->container->get('cms.form_wizard.' . $wizardName);

        $form = $wizard->getStepForm($step);

        /** @var Request $request */
        $request = Request::createFromGlobals();

        if ($request->isMethod($wizard->getStepMethod($step))) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $wizard->flush($step, $form->getData());
            }
        }

        return $this->env->render($template, [
            'form' => $form->createView()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'form_wizard';
    }
}
