<?php
namespace CMS\FormWizardBundle;

use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormFactory;

/**
 * Created by PhpStorm.
 * User: pavel
 * Date: 27.10.15
 * Time: 16:44
 */
class WizardStep
{
    /**
     * @var
     */
    private $name;

    /**
     * @var
     */
    private $type;

    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @var
     */
    private $form;

    private $dataClass;

    /**
     * WizardStep constructor.
     * @param $name
     * @param $type
     */
    public function __construct($name, $type, FormFactory $formFactory)
    {
        $this->formFactory = $formFactory;
        $this->name = $name;
        $this->type = $type;
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
     * @param $data
     * @param array $options
     * @return \Symfony\Component\Form\Form|\Symfony\Component\Form\FormInterface
     */
    public function getForm($data = null, $options = [])
    {
        if (null === $this->form) {
            $type = $this->getType();

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