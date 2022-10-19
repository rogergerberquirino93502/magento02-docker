<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\EntityManager\MetadataPool;
use Firebear\ImportExport\Api\Data\ImportInterface;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationComposite;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot;
use Magento\Framework\DataObject;

/**
 * Class Job
 *
 * @package Firebear\ImportExport\Model\ResourceModel
 */
class Job extends AbstractDb
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    /**
     * Code of "Integrity constraint violation: 1062 Duplicate entry" error
     */
    const ERROR_CODE_DUPLICATE_ENTRY = 23000;

    /**
     * Encryptor
     *
     * @var FieldEncryptor
     */
    protected $encryptor;

    /**
     * Fields that should be serialized before persistence
     *
     * @var array
     */
    protected $_serializableFields = [
        ImportInterface::BEHAVIOR_DATA => [[], []],
        ImportInterface::SOURCE_DATA => [[], []]
    ];

    /**
     * @param Context $context
     * @param MetadataPool $metadataPool
     * @param EntityManager $entityManager
     * @param FieldEncryptor $encryptor
     * @param Snapshot $entitySnapshot
     * @param RelationComposite $entityRelationComposite
     * @param string $connectionName
     */
    public function __construct(
        Context $context,
        MetadataPool $metadataPool,
        EntityManager $entityManager,
        FieldEncryptor $encryptor,
        Snapshot $entitySnapshot,
        RelationComposite $entityRelationComposite,
        $connectionName = null
    ) {
        $this->metadataPool = $metadataPool;
        $this->entityManager = $entityManager;
        $this->encryptor = $encryptor;

        parent::__construct($context, $entitySnapshot, $entityRelationComposite, $connectionName);
    }

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('firebear_import_jobs', 'entity_id');
    }

    /**
     * @param AbstractModel $object
     * @param mixed         $value
     *
     * @return mixed
     * @throws NoSuchEntityException
     */

    public function load(AbstractModel $object, $value, $field = null)
    {
        $this->entityManager->load($object, $value);
        $this->unserialize($object);
        $this->decrypt($object);
    }

    /**
     * Save object object data
     *
     * @param AbstractModel $object
     * @return $this
     * @throws \Exception
     * @throws AlreadyExistsException
     * @api
     */
    public function save(AbstractModel $object)
    {
        $object->setSourceData($this->encryptor->encrypt($object->getSourceData()));
        return parent::save($object);
    }

    /**
     * @inheritDoc
     */
    public function getConnection()
    {
        return $this->metadataPool->getMetadata(ImportInterface::class)->getEntityConnection();
    }

    /**
     * Unserialize serializeable object fields
     *
     * @param DataObject $object
     * @return void
     */
    public function unserialize(DataObject $object)
    {
        foreach ($this->_serializableFields as $field => $parameters) {
            list($serializeDefault, $unserializeDefault) = $parameters;
            $this->_unserializeField($object, $field, $unserializeDefault);
        }
    }

    /**
     * Decrypt encryptable object fields
     *
     * @param DataObject $object
     * @return void
     */
    public function decrypt(DataObject $object)
    {
        $object->setSourceData($this->encryptor->decrypt($object->getSourceData()));
    }
}
