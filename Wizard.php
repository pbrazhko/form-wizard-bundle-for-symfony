<?php
/**
 * Created by PhpStorm.
 * User: pavel
 * Date: 22.10.15
 * Time: 15:10
 */

namespace CMS\FormWizardBundle;


use Symfony\Component\Form\FormFactory;

class Wizard
{
    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @var WizardConfiguration
     */
    private $configuration;

    /**
     * Wizard constructor.
     * @param $configuration
     */
    public function __construct(WizardConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @param mixed $formFactory
     * @return $this
     */
    public function setFormFactory(FormFactory $formFactory)
    {
        $this->formFactory = $formFactory;

        return $this;
    }

    /**
     * @param null $stepName
     * @param null $data
     * @param array $options
     * @return \Symfony\Component\Form\Form|\Symfony\Component\Form\FormInterface
     */
    public function getStepForm($stepName = null, $data = null, $options = array())
    {
        $step = $this->configuration->getFirstStep();

        if (null !== $stepName) {
            $step = $this->configuration->getStep($stepName);
        }

        return $this->formFactory->create(new $step['type'], $data, $options);
    }

    /**
     * @param null $stepName
     * @return mixed
     */
    public function getStepMethod($stepName = null)
    {
        $step = $this->configuration->getFirstStep();

        if (null !== $stepName) {
            $step = $this->configuration->getStep($stepName);
        }

        return $step['method'];
    }

    /**
     * @param $currentStep
     * @return bool
     */
    public function finished($currentStep)
    {
        return $currentStep == $this->configuration->getLastStep();
    }

    /**
     * @param null $step
     * @param $data
     */
    public function flush($step = null, $data)
    {

    }

    /**
     * @param $currentStep
     * @return mixed|null
     */
    public function getNextStepName($currentStep)
    {

        return $this->configuration->getNextStepName($currentStep);
    }
}