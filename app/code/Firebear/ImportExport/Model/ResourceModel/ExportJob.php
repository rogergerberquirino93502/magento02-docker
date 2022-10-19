<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Model\AbstractModel;
use Firebear\ImportExport\Api\Data\ExportInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\DB\Select;
use Magento\Framework\DataObject;

/**
 * Class ExportJob
 *
 * @package Firebear\ImportExport\Model\ResourceModel
 */
class ExportJob extends AbstractDb
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
        ExportInterface::EXPORT_SOURCE => [[], []],
        ExportInterface::BEHAVIOR_DATA => [[], []],
        ExportInterface::SOURCE_DATA => [[], []]
    ];

    /**
     * Define main table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('firebear_export_jobs', 'entity_id');
    }

    /**
     * ExportJob constructor.
     *
     * @param Context $context
     * @param EntityManager $entityManager
     * @param MetadataPool $metadataPool
     * @param FieldEncryptor $encryptor
     * @param null $connectionName
     */
    public function __construct(
        Context $context,
        EntityManager $entityManager,
        MetadataPool $metadataPool,
        FieldEncryptor $encryptor,
        $connectionName = null
    ) {
        $this->entityManager = $entityManager;
        $this->metadataPool = $metadataPool;
        $this->encryptor = $encryptor;

        parent::__construct($context, $connectionName);
    }

    /**
     * @inheritDoc
     */
    public function getConnection()
    {
        return $this->metadataPool->getMetadata(ExportInterface::class)->getEntityConnection();
    }

    /**
     * @param AbstractModel $object
     * @param               $value
     * @param null          $field
     *
     * @return bool
     */
    private function getExportJobId(AbstractModel $object, $value, $field = null)
    {
        $entityMetadata = $this->metadataPool->getMetadata(ExportInterface::class);
        if (!$field) {
            $field = $entityMetadata->getIdentifierField();
        }
        $entityId = $value;
        if ($field != $entityMetadata->getIdentifierField() || $object->getStoreId()) {
            $select = $this->_getLoadSelect($field, $value, $object);
            $select->reset(Select::COLUMNS)
                ->columns($this->getMainTable() . '.' . $entityMetadata->getIdentifierField())
                ->limit(1);
            $result = $this->getConnection()->fetchCol($select);
            $entityId = count($result) ? $result[0] : false;
        }
        return $entityId;
    }

    /**
     * @param AbstractModel $object
     * @param mixed         $value
     * @param null          $field
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function load(AbstractModel $object, $value, $field = null)
    {
        $exportJobId = $this->getExportJobId($object, $value, $field);
        if ($exportJobId) {
            $this->entityManager->load($object, $exportJobId);
            $this->unserialize($object);
            $this->decrypt($object);
            $this->_afterLoad($object);
        }

        return $this;
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
        $object->setExportSource($this->encryptor->encrypt($object->getExportSource()));
        return parent::save($object);
    }

    /**
     * @inheritDoc
     */
    public function delete(AbstractModel $object)
    {
        $this->entityManager->delete($object);
        return $this;
    }

    /**
     * Perform actions after object load
     *
     * @param AbstractModel $object
     */
    protected function _afterLoad(AbstractModel $object)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable('firebear_export_jobs_event'),
            ['event']
        )->where('job_id = ?', $object->getId());

        $events = $this->getConnection()->fetchCol($select);
        $object->setEvent(implode(',', $events ?: []));

        return $this;
    }

    /**
     * Perform actions after object save
     *
     * @param AbstractModel $object
     * @return $this
     */
    protected function _afterSave(AbstractModel $object)
    {
        $connection = $this->getConnection();
        if ($object->getId()) {
            $condition = $connection->quoteInto('job_id=?', $object->getId());
            $connection->delete(
                $this->getTable('firebear_export_jobs_event'),
                $condition
            );
        }
        $data = [];
        $events = is_array($object->getEvent())
            ? $object->getEvent()
            : explode(',', $object->getEvent());

        foreach ($events as $event) {
            $data[] = [
                'job_id' => $object->getId(),
                'event' => $event
            ];
        }
        if (!empty($data)) {
            $connection->insertOnDuplicate(
                $this->getTable('firebear_export_jobs_event'),
                $data
            );
        }
        return $this;
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
        $object->setExportSource($this->encryptor->decrypt($object->getExportSource()));
    }
}
