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
     * @param $type
     * @param $data
     * @return $this
     */
    public function setData($type, $data)
    {
        $this->data[$type] = is_object($data) ? serialize($data) : $data;

        $this->saveData();

        return $this;
    }

    /**
     * @param $type
     * @param null $default
     * @return array
     */
    public function getData($type, $default = null)
    {
        $data = $this->loadData();

        if (isset($data[$type])) {
            return unserialize($data[$type]);
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

    private function loadData()
    {
        if (null === $this->data) {
            $this->session->get($this->dataHashName);
        }

        return $this->data;
    }

    /**
     * Saving data to session
     */
    private function saveData(){
        $this->session->set($this->dataHashName, $this->data);
    }
}