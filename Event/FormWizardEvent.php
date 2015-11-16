<?php
/**
 * Created by PhpStorm.
 * User: pavel
 * Date: 16.11.15
 * Time: 12:52
 */

namespace CMS\FormWizardBundle\Event;


use CMS\FormWizardBundle\Wizard;
use Symfony\Component\EventDispatcher\Event;

class FormWizardEvent extends Event
{
    protected $stepName;

    protected $wizard;

    /**
     * PrePersistStep constructor.
     * @param Wizard $wizard
     * @param $stepName
     */
    public function __construct(Wizard $wizard, $stepName)
    {
        $this->wizard = $wizard;
        $this->stepName = $stepName;
    }

    /**
     * @return Wizard
     */
    public function getWizard()
    {
        return $this->wizard;
    }

    /**
     * @return mixed
     */
    public function getStepName()
    {
        return $this->stepName;
    }
}