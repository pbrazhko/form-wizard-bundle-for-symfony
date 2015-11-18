<?php
/**
 * Created by PhpStorm.
 * User: pavel
 * Date: 22.10.15
 * Time: 15:14
 */

namespace CMS\FormWizardBundle;


use Symfony\Component\Form\FormFactory;

class WizardConfiguration
{
    private $steps = [];


    /**
     * WizardConfiguration constructor.
     * @param array $steps
     * @param FormFactory $formFactory
     */
    public function __construct(array $steps, FormFactory $formFactory)
    {
        foreach ($steps as $name => $properties) {
            $this->steps[$name] = new WizardStep(
                $name,
                $properties['type'],
                $properties['condition'],
                $properties['template'],
                $formFactory
            );
        }
    }

    /**
     * @return array
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * @param $name
     * @return WizardStep
     */
    public function getStep($name)
    {
        if (isset($this->steps[$name])) {
            return $this->steps[$name];
        }

        return null;
    }

    /**
     * @return mixed
     */
    public function getFirstStep()
    {
        return reset($this->steps);
    }

    /**
     * @return mixed
     */
    public function getLastStep()
    {
        return end($this->steps);
    }

    /**
     * @param null $currentStepName
     * @return WizardStep|null
     */
    public function getNextStep($currentStepName = null)
    {
        $currentStep = null === $currentStepName ? $this->getFirstStep() : $this->getStep($currentStepName);

        $nextStep = false;

        foreach ($this->steps as $name => $step) {
            if ($name == $currentStep->getName()) {
                $nextStep = next($this->steps);
                break;
            }
        }

        return $nextStep;
    }

    /**
     * @param array $steps
     * @return $this
     */
    public function setSteps($steps)
    {
        $this->steps = $steps;

        return $this;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return md5(serialize($this));
    }
}