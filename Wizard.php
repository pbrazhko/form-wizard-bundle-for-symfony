<?php
/**
 * Created by PhpStorm.
 * User: pavel
 * Date: 22.10.15
 * Time: 15:10
 */

namespace CMS\FormWizardBundle;


use CMS\FormWizardBundle\Event\PostPersistStepEvent;
use CMS\FormWizardBundle\Event\PrePersistStep;
use CMS\FormWizardBundle\Event\StepEvents;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
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
     * @param $configuration
     */
    public function __construct(WizardConfiguration $configuration)
    {
        $this->configuration = $configuration;
        $this->expressionLanguage = new ExpressionLanguage();
        $this->dataStorage = new WizardDataStorage($this->configuration->getHash());
        $this->eventDispatcher = new EventDispatcher();
    }

    /**
     * @param EntityManagerInterface $flusher
     * @return $this
     */
    public function setFlusher($flusher)
    {
        $this->flusher = $flusher;

        $this->dataStorage->setFlusher($this->flusher);

        return $this;
    }

    /**
     * @param null $stepName
     * @param null $data
     * @param array $options
     * @return \Symfony\Component\Form\Form|\Symfony\Component\Form\FormInterface
     */
    public function getForm($stepName = null, $data = null, $options = array())
    {
        $step = null === $stepName ? $this->configuration->getFirstStep() : $this->configuration->getStep($stepName);

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
     * @param $currentStep
     * @return bool
     */
    public function finished($currentStep)
    {
        return $currentStep == $this->configuration->getLastStep() || false === $this->getNextStep($currentStep);
    }

    /**
     * @param null $stepName
     * @param $data
     * @return $this
     */
    public function flush($stepName = null, $data)
    {
        $step = null === $stepName ? $this->configuration->getFirstStep() : $this->configuration->getStep($stepName);

        $dataName = null === ($dataType = $step->getDataType()) ? $step->getName() : $dataType;

        $this->dataStorage->setData($dataName, $data);

        if ($this->finished($stepName) || $this->configuration->getPersist() == WizardConfiguration::PERSIST_TYPE_STEP_BY_STEP) {

            $this->eventDispatcher->dispatch(StepEvents::PRE_PERSIST_STEP_EVENT, new PrePersistStep($step));

            $this->dataStorage->flush();

            $this->eventDispatcher->dispatch(StepEvents::POST_PERSIST_STEP_EVENT, new PostPersistStepEvent($step));

            if($this->finished($stepName)){

                $this->eventDispatcher->dispatch(StepEvents::FLUSH_WIZARD_EVENT);
                $this->dataStorage->clear();
            }
        }

        return $this;
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
     * @param WizardStep $step
     * @param $eventName
     * @return string
     */
    private function executeEvent(WizardStep $step, $eventName){
        if (null !== $step->getEvent($eventName)) {
            $result = $this->expressionLanguage->evaluate($step->getEvent($eventName), $this->getValues());
        }

        var_dump($result);
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

            $values[$name] = $this->dataStorage->getData(
                null === $dataType ? $step->getName() : $dataType,
                null === $dataType ? array() : new $dataType
            );
        }

        return $values;
    }
}