<?php
/**
 * Created by PhpStorm.
 * User: pavel
 * Date: 22.10.15
 * Time: 15:14
 */

namespace CMS\FormWizardBundle;


class WizardConfiguration
{
    private $steps = [];

    private $persist;

    /**
     * WizardConfiguration constructor.
     * @param array $steps
     * @param $persist
     */
    public function __construct(array $steps, $persist)
    {
        $this->steps = $steps;
        $this->persist = $persist;
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
     * @return null
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
     * @return mixed
     */
    public function getFirstStepName()
    {
        $nameSteps = array_keys($this->steps);

        return reset($nameSteps);
    }

    /**
     * @return mixed
     */
    public function getLastStepName()
    {
        $nameSteps = array_keys($this->steps);

        return end($nameSteps);
    }

    /**
     * @param $currentStep
     * @return mixed|null
     */
    public function getNextStepName($currentStep)
    {
        if (null === $currentStep) {
            $currentStep = $this->getFirstStepName();
        }

        $nextStep = null;

        $nameSteps = array_keys($this->steps);
        foreach ($nameSteps as $name) {
            if ($name == $currentStep) {
                $nextStep = next($nameSteps);
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
     * @return mixed
     */
    public function getPersist()
    {
        return $this->persist;
    }
}