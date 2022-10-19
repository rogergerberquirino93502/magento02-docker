<?php

namespace Firebear\ImportExport\Model\ResourceModel\Job;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\EntityManager\MetadataPool;
use Firebear\ImportExport\Api\Data\ImportMappingInterface;
use Magento\Framework\EntityManager\EntityManager;

/**
 * Class Mapping
 *
 * @package Firebear\ImportExport\Model\ResourceModel\Job
 */
class Mapping extends AbstractDb
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
     * @param Context       $context
     * @param MetadataPool  $metadataPool
     * @param EntityManager $entityManager
     * @param string        $connectionName
     */
    public function __construct(
        Context $context,
        MetadataPool $metadataPool,
        EntityManager $entityManager,
        $connectionName = null
    ) {
        $this->metadataPool = $metadataPool;
        $this->entityManager = $entityManager;
        parent::__construct($context, $connectionName);
    }

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('firebear_import_job_mapping', 'entity_id');
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
    }

    /**
     * @inheritDoc
     */
    public function getConnection()
    {
        return $this->metadataPool->getMetadata(ImportMappingInterface::class)->getEntityConnection();
    }
}
