<?php
/**
 * Created by PhpStorm.
 * User: pavel
 * Date: 22.10.15
 * Time: 15:10
 */

namespace CMS\FormWizardBundle;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormFactory;

class Wizard
{
    /**
     * @var FormFactory
     */
    private $formFactory;

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
     * @param mixed $formFactory
     * @return $this
     */
    public function setFormFactory(FormFactory $formFactory)
    {
        $this->formFactory = $formFactory;

        return $this;
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

        return $this;
    }

    /**
     * @param null $stepName
     * @param null $data
     * @param array $options
     * @return \Symfony\Component\Form\Form|\Symfony\Component\Form\FormInterface
     */
    public function getStepForm($stepName = null, $data = null, $options = array())
    {
        if (null === $stepName) {
            $stepName = $this->configuration->getFirstStepName();
        }

        $step = $this->configuration->getStep($stepName);

        $form = $this->formFactory->create(new $step['type'], $data, $options);

        $formConfig = $form->getConfig();

        if(null === $data && (null !== $dataClass = $formConfig->getDataClass())){
            $data = $this->dataStorage->getData($stepName, new $dataClass);

            $data = $this->flusher->merge($data);
        } else {
            $data = $this->dataStorage->getData($stepName, array());
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
        return $currentStep == $this->configuration->getLastStep();
    }

    /**
     * @param null $stepName
     * @param $data
     * @return $this
     */
    public function flush($stepName = null, $data)
    {
        if(null === $stepName){
            $stepName = $this->configuration->getFirstStepName();
        }

        $this->flusher->persist($data);

        if ($this->configuration->getPersist() == WizardConfiguration::PERSIST_TYPE_POST_PRESET) {
            $this->flusher->flush($data);
        } else {
            $this->dataStorage->setData($stepName, $data);
        }

        return $this;
    }

    /**
     * @param $currentStep
     * @return mixed|null
     */
    public function getNextStepName($currentStep)
    {
        $nextStep = $this->configuration->getNextStep($currentStep);
        $nextStepName = $this->configuration->getNextStepName($currentStep);

        $values = [];

        foreach($this->configuration->getSteps() as $name => $parameters){
            $values[$name] = $this->dataStorage->getData($name);
        }

        if (isset($nextStep['condition'])) {
            if(!$this->expressionLanguage->evaluate($nextStep['condition'], $values)){
                return $this->getNextStepName($nextStepName);
            }
        }

        return $this->configuration->getNextStepName($currentStep);
    }
}