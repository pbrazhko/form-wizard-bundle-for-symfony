<?php
/**
 * Created by PhpStorm.
 * User: pavel
 * Date: 22.10.15
 * Time: 15:10
 */

namespace CMS\FormWizardBundle;


use CMS\FormWizardBundle\Event\FormWizardEvent;
use CMS\FormWizardBundle\Event\PostPersistStepEvent;
use CMS\FormWizardBundle\Event\PrePersistStep;
use CMS\FormWizardBundle\Event\PrePersistStepEvent;
use CMS\FormWizardBundle\Event\StepEvents;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class Wizard
{

    private $name;
    /**
     * @var WizardConfiguration
     */
    private $configuration;

    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;

    /**
     * @var WizardStorage
     */
    private $storage;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * wizard constructor.
     * @param $name
     * @param WizardConfiguration $configuration
     * @param EventDispatcherInterface $eventDispatcher
     * @param EntityManagerInterface $entityManager
     */
    public function __construct($name, WizardConfiguration $configuration, EventDispatcherInterface $eventDispatcher, EntityManagerInterface $entityManager)
    {
        $this->name = $name;
        $this->configuration = $configuration;
        $this->expressionLanguage = new ExpressionLanguage();
        $this->storage = new WizardStorage($this->configuration->getHash(), $entityManager);
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return WizardStorage
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * @param null $stepName
     * @param null $data
     * @param array $options
     * @return \Symfony\Component\Form\Form|\Symfony\Component\Form\FormInterface
     */
    public function getForm($stepName, $data = null, $options = array())
    {
        $step = $this->configuration->getStep($stepName);

        if (!$this->executeCondition($step)) {
            return $this->getForm($this->configuration->getNextStep($step->getName()), $data, $options);
        }

        $this->eventDispatcher->dispatch(StepEvents::BUILD_STEP_FORM, new FormWizardEvent($this, $stepName));

        $form = $step->getForm($data, $options);
        $dataClass = $step->getDataType();

        if (null === $data && null !== $dataClass) {
            $data = $this->storage->getData($dataClass, new $dataClass);
        } else {
            $data = $this->storage->getData($step->getName(), array());
        }

        $form->setData($data);

        return $form;
    }

    /**
     * @param $currentStepName
     * @return bool
     */
    public function finished($currentStepName)
    {
        $currentStep = $this->getStep($currentStepName);

        return $currentStep == $this->configuration->getLastStep() || false === $this->getNextStep($currentStepName);
    }

    /**
     * @param null $stepName
     * @param $data
     * @return $this
     */
    public function flush($stepName, $data)
    {
        $step = $this->configuration->getStep($stepName);

        $dataName = null === ($dataType = $step->getDataType()) ? $step->getName() : $dataType;

        $this->eventDispatcher->dispatch(StepEvents::PRE_SET_DATA_STEP, new FormWizardEvent($this, $stepName));

        $this->storage->setData($dataName, $data);

        $this->eventDispatcher->dispatch(StepEvents::POST_SET_DATA_STEP, new FormWizardEvent($this, $stepName));

        $step->setData($data);

        if ($this->finished($stepName)) {
            $this->eventDispatcher->dispatch(StepEvents::FLUSH_WIZARD_EVENT, new FormWizardEvent($this, $stepName));

            $this->storage->flush($dataName, $data);
            $this->storage->clear();
        }

        return $this;
    }

    /**
     * @param $stepName
     * @return WizardStep
     */
    public function getStep($stepName)
    {
        $step = $this->configuration->getStep($stepName);
        $dataName = null === ($dataType = $step->getDataType()) ? $step->getName() : $dataType;
        $step->setData($this->storage->getData($dataName));

        return $step;
    }

    /**
     * @param $currentStepName
     * @return mixed|null
     */
    public function getNextStep($currentStepName)
    {
        $nextStep = $this->configuration->getNextStep($currentStepName);

        if (false != $nextStep && !$this->executeCondition($nextStep)) {
            return $this->getNextStep($nextStep->getName());
        }

        return $nextStep;
    }

    public function getFirstStep()
    {
        return $this->configuration->getFirstStep();
    }

    public function getLastStep()
    {
        return $this->configuration->getLastStep();
    }

    /**
     * @param $step
     * @return bool|string
     */
    private function executeCondition(WizardStep $step)
    {
        if (null !== $step->getCondition()) {
            return $this->expressionLanguage->evaluate($step->getCondition(), $this->getValues());
        }

        return true;
    }

    /**
     * @return array
     */
    private function getValues()
    {
        $values = [];
        /**
         * @var string $name
         * @var WizardStep $step
         */
        foreach ($this->configuration->getSteps() as $name => $step) {
            $dataType = $step->getDataType();

            $values[$name] = $this->storage->getData(
                null === $dataType ? $step->getName() : $dataType,
                null === $dataType ? array() : new $dataType
            );
        }

        return $values;
    }
}