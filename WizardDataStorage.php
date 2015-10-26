<?php
/**
 * Created by PhpStorm.
 * User: pavel
 * Date: 26.10.15
 * Time: 17:40
 */

namespace CMS\FormWizardBundle;


use Symfony\Component\HttpFoundation\Session\Session;

class WizardDataStorage
{
    private $data;

    /**
     * @var Session
     */
    private $session;

    private $dataHashName;

    /**
     * WizardDataStorage constructor.
     * @param $dataHashName
     */
    public function __construct($dataHashName)
    {
        $this->session = new Session();
        $this->dataHashName = $dataHashName;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        if(null === $this->data){
            $this->data = $this->session->get($this->dataHashName, []);
        }

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
     * @param $stepName
     * @param $data
     * @return $this
     */
    public function setStepData($stepName, $data){
        $this->data[$stepName] = is_object($data)? serialize($data) : $data;

        $this->saveData();

        return $this;
    }

    /**
     * @param $stepName
     * @param null $default
     * @return array
     */
    public function getStepData($stepName, $default = null){
        $data = $this->getData();

        if(isset($data[$stepName])){
            return unserialize($data[$stepName]);
        }

        return $default;
    }

    /**
     * @return mixed
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param mixed $session
     * @return $this
     */
    public function setSession($session)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * Saving data to session
     */
    private function saveData(){
        $this->session->set($this->dataHashName, $this->data);
    }
}