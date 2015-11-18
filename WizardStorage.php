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

class WizardStorage implements \IteratorAggregate
{
    private $data = [];

    /**
     * @var Session
     */
    private $session;

    /**
     * @var string
     */
    private $hash;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * WizardStorage constructor.
     * @param $hash
     * @param EntityManagerInterface $entityManager
     */
    public function __construct($hash, EntityManagerInterface $entityManager)
    {
        $this->session = new Session();
        $this->hash = $hash;
        $this->entityManager = $entityManager;

        $this->loadData();
    }

    /**
     * @param $dataName
     * @param $data
     * @return $this
     */
    public function setData($dataName, $data)
    {
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
        if (isset($this->data[$dataName])) {
            return $this->data[$dataName];
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
     * @return void
     */
    public function flush()
    {
        try {
            foreach ($this->data as $data) {
                $metaDataClass = $this->entityManager->getClassMetadata(get_class($data));

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
                            $relatedEntitiesState = $this->entityManager->getUnitOfWork()->getEntityState($relatedEntities);

                            if ($relatedEntitiesState === UnitOfWork::STATE_DETACHED) {
                                $metaDataClass->setFieldValue($data, $assoc['fieldName'], $this->entityManager->merge($relatedEntity));
                            }
                        }
                    } else if ($relatedEntities !== null) {
                        $relatedEntitiesState = $this->entityManager->getUnitOfWork()->getEntityState($relatedEntities);

                        if ($relatedEntitiesState === UnitOfWork::STATE_DETACHED) {
                            $metaDataClass->setFieldValue($data, $assoc['fieldName'], $this->entityManager->merge($relatedEntities));
                        }

                    }
                }

                $this->entityManager->persist($data);
            }

            $this->entityManager->flush();

        } catch (ORMException $e) {
            $this->entityManager->rollback();
        }
    }

    /**
     * Load data from session
     *
     * @return array
     */
    private function loadData()
    {
        if (!count($this->data)) {
            $this->data = $this->session->get($this->hash, []);
        }

        return $this->data;
    }

    /**
     * Saving data to session
     *
     * @return void
     */
    private function saveData()
    {
        $this->session->set($this->hash, $this->data);
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
     * Clear data in session
     *
     * @return void
     */
    public function clear()
    {
        $this->data = [];
        $this->saveData();
    }
}