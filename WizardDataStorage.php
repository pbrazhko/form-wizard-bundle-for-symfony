<?php
/**
 * Created by PhpStorm.
 * User: pavel
 * Date: 26.10.15
 * Time: 17:40
 */

namespace CMS\FormWizardBundle;


use CMS\FormWizardBundle\Exception\InvalidArgumentException;
use Doctrine\Common\Collections\Collection;
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
        $metaDataClass = $this->flusher->getClassMetadata($data);

        $this->data[$dataName] = $metaDataClass->getFieldValue($data, $metaDataClass->getIdentifier());

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
        $data = $default;

        if (isset($this->data[$dataName])) {
            $data = $this->data[$dataName];
        }

        return $this->flusher->getUnitOfWork()->tryGetById($data, get_class($data));
    }

    public function refreshData()
    {
        foreach ($this->session->get($this->dataHashName, []) as $data) {
            $this->merge($data);
        }
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
     * @param $dataName
     */
    public function flush($dataName, $data)
    {
        try {
            $this->refreshData();

            $this->flusher->persist($data);
            $this->flusher->flush();

            $this->setData($dataName, $data);
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
     * @return object
     */
    private function merge($data)
    {
        if(is_object($data)) {
            $metaDataClass = $this->flusher->getClassMetadata(get_class($data));

            $assocFields = $metaDataClass->getAssociationMappings();

            foreach ($assocFields as $assoc) {
                $relatedEntities = $metaDataClass->reflFields[$assoc['fieldName']]->getValue($data);

                if ($relatedEntities instanceof Collection) {
                    if ($relatedEntities === $metaDataClass->reflFields[$assoc['fieldName']]->getValue($data)) {
                        continue;
                    }

                    if ($relatedEntities instanceof PersistentCollection) {
                        // Unwrap so that foreach() does not initialize
                        $relatedEntities = $relatedEntities->unwrap();
                    }

                    foreach ($relatedEntities as $relatedEntity) {
                        $metaDataClass->setFieldValue($data, $assoc['fieldName'], $this->merge($relatedEntity));
                    }
                } else if ($relatedEntities !== null) {
                    $metaDataClass->setFieldValue($data, $assoc['fieldName'], $this->merge($relatedEntities));
                }
            }
        }

        return $this->flusher->merge($data);
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
        $this->data = [];
        $this->saveData();
    }
}