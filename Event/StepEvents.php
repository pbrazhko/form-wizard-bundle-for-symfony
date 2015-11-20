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
    const BUILD_STEP_FORM = 'form.wizard.build_form';
    const PRE_SET_DATA_STEP = 'form.wizard.pre_set_data';
    const POST_SET_DATA_STEP = 'form.wizard.post_set_data';
    const FLUSH_WIZARD_EVENT = 'form.wizard.flush';
}