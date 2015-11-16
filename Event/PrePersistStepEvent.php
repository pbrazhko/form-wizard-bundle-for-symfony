<?php
/**
 * Created by PhpStorm.
 * User: work-pc
 * Date: 14.11.15
 * Time: 21:24
 */

namespace CMS\FormWizardBundle\Event;

use CMS\FormWizardBundle\WizardStep;
use Symfony\Component\EventDispatcher\Event;

class PrePersistStepEvent extends Event
{
    protected $step;

    /**
     * PrePersistStep constructor.
     * @param $step
     */
    public function __construct(WizardStep $step)
    {
        $this->step = $step;
    }

    /**
     * @return mixed
     */
    public function getStep()
    {
        return $this->step;
    }
}