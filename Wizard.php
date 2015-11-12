<?php
/**
 * Created by PhpStorm.
 * User: pavel
 * Date: 22.10.15
 * Time: 15:10
 */

namespace CMS\FormWizardBundle;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
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
     * Wizard constructor.
     * @param $configuration
     */
    public function __construct(WizardConfiguration $configuration)
    {
        $this->configuration = $configuration;
        $this->expressionLanguage = new ExpressionLanguage();
        $this->dataStorage = new WizardDataStorage($this->configuration->getHash());
    }

    /**
     * @return EntityManagerInterface
     */
    public function getFlusher()
    {
        return $this->flusher;
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
        if (null === $stepName) {
            $step = $this->configuration->getFirstStep();
        } else {
            $step = $this->configuration->getStep($stepName);
        }

        $values = [];
        /**
         * @var string $name
         * @var WizardStep $step
         */
        foreach ($this->configuration->getSteps() as $name => $step) {
            $dataType = $step->getDataType();

            $values[$name] = $this->dataStorage->getData(
                null === $dataType ? WizardDataStorage::DATA_TYPE_ARRAY : WizardDataStorage::DATA_TYPE_OBJECT,
                null === $dataType ? $step->getName() : $dataType,
                null === $dataType ? array() : new $dataType
            );
        }

        if (null !== $step->getCondition()) {
            if (!$this->expressionLanguage->evaluate($step->getCondition(), $values)) {
                return $this->getForm($this->getNextStep($step->getName()), $data, $options);
            }
        }


        $form = $step->getForm($data, $options);
        $dataClass = $step->getDataType();

        if (null === $data && null !== $dataClass) {
            $data = $this->dataStorage->getData(WizardDataStorage::DATA_TYPE_OBJECT, $dataClass, new $dataClass);
        } else {
            $data = $this->dataStorage->getData(WizardDataStorage::DATA_TYPE_ARRAY, $step->getName(), array());
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
        if(null === $stepName){
            $step = $this->configuration->getFirstStep();
        } else {
            $step = $this->configuration->getStep($stepName);
        }

        $dataType = $step->getDataType();

        if (null === $dataType) {
            $this->dataStorage->setData(WizardDataStorage::DATA_TYPE_ARRAY, $step->getName(), $data);
        } else {
            $this->dataStorage->setData(WizardDataStorage::DATA_TYPE_OBJECT, $dataType, $data);
        }

        if ($this->finished($stepName) || $this->configuration->getPersist() == WizardConfiguration::PERSIST_TYPE_STEP_BY_STEP) {
            try {
                foreach ($this->dataStorage as $data) {
                    $this->flusher->persist($data);
                }

                $this->flusher->flush();
            } catch (ORMException $e) {
                $this->flusher->rollback();
            }

            if ($this->finished($stepName)) {
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

        $values = [];
        /**
         * @var string $name
         * @var WizardStep $step
         */
        foreach($this->configuration->getSteps() as $name => $step){
            $dataType = $step->getDataType();

            $values[$name] = $this->dataStorage->getData(
                null === $dataType ? WizardDataStorage::DATA_TYPE_ARRAY : WizardDataStorage::DATA_TYPE_OBJECT,
                null === $dataType ? $step->getName() : $dataType,
                null === $dataType ? array() : new $dataType
            );
        }

        if (false !== $nextStep && null !== $nextStep->getCondition()) {
            if(!$this->expressionLanguage->evaluate($nextStep->getCondition(), $values)){
                return $this->getNextStep($nextStep->getName());
            }
        }

        return $nextStep;
    }
}