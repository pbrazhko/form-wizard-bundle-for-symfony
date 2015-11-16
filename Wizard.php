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
    /**
     * @var WizardConfiguration
     */
    private $configuration;

    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;

    /**
     * @var EntityManagerInterface
     */
    private $flusher;

    /**
     * @var WizardDataStorage
     */
    private $dataStorage;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * Wizard constructor.
     * @param WizardConfiguration $configuration
     * @param EventDispatcherInterface $eventDispatcher
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(WizardConfiguration $configuration, EventDispatcherInterface $eventDispatcher, EntityManagerInterface $entityManager)
    {
        $this->configuration = $configuration;
        $this->expressionLanguage = new ExpressionLanguage();
        $this->dataStorage = new WizardDataStorage($this->configuration->getHash(), $entityManager);
        $this->eventDispatcher = $eventDispatcher;
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

        $form = $step->getForm($data, $options);
        $dataClass = $step->getDataType();

        if (null === $data && null !== $dataClass) {
            $data = $this->dataStorage->getData($dataClass, new $dataClass);
        } else {
            $data = $this->dataStorage->getData($step->getName(), array());
        }

        $form->setData($data);

        return $form;
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

        $this->dataStorage->setData($dataName, $data);
        $step->setData($data);

        if ($this->finished($stepName) || $this->configuration->getPersist() == WizardConfiguration::PERSIST_TYPE_STEP_BY_STEP) {

            $this->eventDispatcher->dispatch(StepEvents::PRE_PERSIST_STEP_EVENT, new FormWizardEvent($this, $stepName));

            $this->dataStorage->flush();

            $this->eventDispatcher->dispatch(StepEvents::POST_PERSIST_STEP_EVENT, new FormWizardEvent($this, $stepName));

            if($this->finished($stepName)){

                $this->eventDispatcher->dispatch(StepEvents::FLUSH_WIZARD_EVENT, new FormWizardEvent($this, $stepName));
                $this->dataStorage->clear();
            }
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
        $step->setData($this->dataStorage->getData($dataName));

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
    public function getValues()
    {
        $values = [];
        /**
         * @var string $name
         * @var WizardStep $step
         */
        foreach ($this->configuration->getSteps() as $name => $step) {
            $dataType = $step->getDataType();

            $values[$name] = $this->dataStorage->getData(
                null === $dataType ? $step->getName() : $dataType,
                null === $dataType ? array() : new $dataType
            );
        }

        return $values;
    }
}