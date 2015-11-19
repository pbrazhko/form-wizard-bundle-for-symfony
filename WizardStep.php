<?php
namespace CMS\FormWizardBundle;

use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;

/**
 * Created by PhpStorm.
 * User: pavel
 * Date: 27.10.15
 * Time: 16:44
 */
class WizardStep
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $condition;

    /**
     * @var string | null
     */
    private $template;

    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @var FormInterface
     */
    private $form;

    /**
     * @var object
     */
    private $data;

    /**
     * @var string
     */
    private $dataClass;

    /**
     * WizardStep constructor.
     * @param $name
     * @param $type
     * @param null $condition
     * @param null $template
     * @param FormFactory $formFactory
     */
    public function __construct($name, $type, $condition = null, $template = null, FormFactory $formFactory)
    {
        $this->template = $template;
        $this->formFactory = $formFactory;
        $this->name = $name;
        $this->type = $type;
        $this->condition = $condition;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @param string $condition
     *
     * @return WizardStep
     */
    public function setCondition($condition)
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param null|string $template
     * @return $this
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * @param $data
     * @param array $options
     * @return \Symfony\Component\Form\Form|\Symfony\Component\Form\FormInterface
     */
    public function getForm($data = null, $options = [])
    {
        if (null === $this->form) {
            $type = $this->getType();

            $options = array_merge($options, [
                'validation_groups' => [$this->getName()]
            ]);

            $this->form = $this->formFactory->create(new $type, $data, $options);
        }

        return $this->form;
    }

    /**
     * @return mixed
     */
    public function getDataType()
    {
        if (null === $this->dataClass) {
            $form = $this->getForm();

            $formConfiguration = $form->getConfig();

            $this->dataClass = $formConfiguration->getOption('data_class');
        }

        return $this->dataClass;
    }

    /**
     * @param $eventName
     * @return mixed
     */
    public function hasEvent($eventName)
    {
        return isset($this->events[$eventName]);
    }

    /**
     * @param $eventName
     * @return WizardStep
     */
    public function getEvent($eventName)
    {
        if($this->hasEvent($eventName)){
            return $this->events[$eventName];
        }

        return null;
    }

    /**
     * serialize() checks if your class has a function with the magic name __sleep.
     * If so, that function is executed prior to any serialization.
     * It can clean up the object and is supposed to return an array with the names of all variables of that object that should be serialized.
     * If the method doesn't return anything then NULL is serialized and E_NOTICE is issued.
     * The intended use of __sleep is to commit pending data or perform similar cleanup tasks.
     * Also, the function is useful if you have very large objects which do not need to be saved completely.
     *
     * @return array|NULL
     * @link http://php.net/manual/en/language.oop5.magic.php#language.oop5.magic.sleep
     */
    function __sleep()
    {
        return ['name', 'type'];
    }


}