<?php
/**
 * Created by PhpStorm.
 * User: pavel
 * Date: 26.10.15
 * Time: 17:40
 */

namespace CMS\FormWizardBundle;


use CMS\FormWizardBundle\Exception\InvalidArgumentException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\ORM\UnitOfWork;
use Symfony\Component\HttpFoundation\Session\Session;
use Traversable;

class WizardDataStorage implements \IteratorAggregate
{
    private $data = [];

    /**
     * @var Session
     */
    private $session;

    /**
     * @var string
     */
    private $dataHashName;

    /**
     * @var EntityManagerInterface
     */
    private $flusher;

    /**
     * @var array
     */
    private $selectedData = [];

    /**
     * WizardDataStorage constructor.
     * @param $dataHashName
     */
    public function __construct($dataHashName, EntityManagerInterface $entityManager)
    {
        $this->session = new Session();
        $this->dataHashName = $dataHashName;
        $this->flusher = $entityManager;

        $this->loadData();
    }

    /**
     * @param $dataName
     * @param $data
     * @return $this
     */
    public function setData($dataName, $data)
    {
        if (is_object($data)) {
            $this->flusher->detach($data);
        }

        $this->data[$dataName] = $data;

        $this->saveData();

        return $this;
    }

    /**
     * @param null $dataName
     * @param null $default
     * @return array
     */
    public function getData($dataName, $default = null)
    {
        if (isset($this->selectedData[$dataName])) {
            return $this->selectedData[$dataName];
        }

        $data = $default;

        if (isset($this->data[$dataName])) {
            $data = $this->data[$dataName];

            if (is_object($data)) {
                $this->merge($data);
            }

            $this->selectedData[$dataName] = $data;
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

    /**
     * @return void
     */
    public function flush(){
        try {
            $this->flusher->flush();
        } catch (ORMException $e) {
            $this->flusher->rollback();
        }
    }

    /**
     * @return mixed
     */
    private function loadData()
    {
        if (!count($this->data)) {
            $this->data = $this->session->get($this->dataHashName, []);
        }

        foreach ($this->data as $data) {
            $this->merge($data);
        }

        return $this->data;
    }

    /**
     * Saving data to session
     */
    private function saveData()
    {
        $this->session->set($this->dataHashName, $this->data);
    }

    /**
     * @param $data
     */
    private function merge($data)
    {
        if(is_object($data)) {
            $this->flusher->persist($data);
        }
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     */
    public function getIterator()
    {
        return new \ArrayIterator(array_keys($this->data));
    }

    /**
     *
     */
    public function clear()
    {
        $this->data = null;
        $this->saveData();
    }
}