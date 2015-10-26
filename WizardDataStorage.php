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

    /**
     * WizardDataStorage constructor.
     * @param $dataHashName
     */
    public function __construct($dataHashName)
    {
        $this->session = new Session();

        $this->data = $this->session->get($dataHashName, array());
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
}