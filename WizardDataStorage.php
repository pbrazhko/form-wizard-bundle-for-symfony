<?php
/**
 * Created by PhpStorm.
 * User: pavel
 * Date: 26.10.15
 * Time: 17:40
 */

namespace CMS\FormWizardBundle;


use CMS\FormWizardBundle\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Session\Session;

class WizardDataStorage
{
    const DATA_TYPE_ARRAY = 1;
    const DATA_TYPE_OBJECT = 2;

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
     * @param $dataType
     * @param $dataName
     * @param $data
     * @return $this
     */
    public function setData($dataType, $dataName, $data)
    {
        if(!in_array($dataType, [self::DATA_TYPE_ARRAY, self::DATA_TYPE_OBJECT])){
            throw new InvalidArgumentException('Data type is not supported!');
        }

        if($dataType == self::DATA_TYPE_OBJECT){
            $data = serialize($data);
        }

        $this->data[$dataName] = $data;

        $this->saveData();

        return $this;
    }

    /**
     * @param $dataType
     * @param null $default
     * @param null $dataName
     * @return array
     */
    public function getData($dataType, $dataName, $default = null)
    {
        $allData = $this->loadData();

        $data = $default;

        if (isset($allData[$dataName])) {
            $data = $allData[$dataName];
        }

        if($dataType == self::DATA_TYPE_OBJECT && is_string($data)){
            $data = unserialize($data);
        }

        return $data;
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
            $this->data = $this->session->get($this->dataHashName);
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