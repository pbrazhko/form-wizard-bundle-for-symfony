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
        $step = $this->configuration->getFirstStep();

        if (null !== $stepName) {
            $step = $this->configuration->getStep($stepName);
        }

        return $this->formFactory->create(new $step['type'], $data, $options);
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
     */
    public function flush($stepName = null, $data)
    {
        $step = $this->configuration->getStep($stepName);

        if ($this->configuration->getPersist() == WizardConfiguration::PERSIST_TYPE_POST_PRESET) {
            $this->flusher->flush($data);
        } else {

        }
    }

    /**
     * @param $currentStep
     * @return mixed|null
     */
    public function getNextStepName($currentStep)
    {
        $nextStep = $this->configuration->getNextStep($currentStep);

        if (isset($nextStep['condition'])) {
            $result = $this->expressionLanguage->evaluate($nextStep['condition'], $this->dataStorage);
        }

        return $this->configuration->getNextStepName($currentStep);
    }
}