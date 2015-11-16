<?php
/**
 * Created by PhpStorm.
 * User: work-pc
 * Date: 14.11.15
 * Time: 21:27
 */

namespace CMS\FormWizardBundle\Event;


final class StepEvents
{
    const PRE_PERSIST_STEP_EVENT = 'form.wizard.pre_persist.step';
    const POST_PERSIST_STEP_EVENT = 'form.wizard.post_persist.step';
    const FLUSH_WIZARD_EVENT = 'form.wizard.flush.wizard';
}