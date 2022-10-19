<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\ResourceModel\Job;

use Firebear\ImportExport\Api\Data\JobReplacingInterface;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

/**
 * Class Replacing
 * @package Firebear\ImportExport\Model\ResourceModel\Job
 */
class Replacing extends AbstractDb
{
    /** @var EntityManager */
    protected $entityManager;

    /** @var MetadataPool */
    protected $metadataPool;

    /**
     * @param Context $context
     * @param MetadataPool $metadataPool
     * @param EntityManager $entityManager
     * @param string $connectionName
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
     * @param AbstractModel $object
     * @param mixed $value
     *
     * @return mixed
     * @throws \LogicException
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
        return $this->metadataPool->getMetadata(JobReplacingInterface::class)->getEntityConnection();
    }

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init('firebear_import_job_replacing', JobReplacingInterface::ENTITY_ID);
    }

    /**
     * @inheritDoc
     */
    public function saveReplacing($object)
    {
        $data = [];
        foreach ($object->getReplacing() as $item) {
            $data[] = [
                JobReplacingInterface::ENTITY_ID => $item->getId(),
                JobReplacingInterface::JOB_ID => $object->getId(),
                JobReplacingInterface::ENTITY_TYPE => $item->getEntityType(),
                JobReplacingInterface::TARGET => $item->getTarget(),
                JobReplacingInterface::IS_CASE_SENSITIVE => $item->getIsCaseSensitive(),
                JobReplacingInterface::FIND => $item->getFind(),
                JobReplacingInterface::REPLACE => $item->getReplace(),
                JobReplacingInterface::ATTRIBUTE_CODE => $item->getAttributeCode()
            ];
        }
        if ($data) {
            $this->getConnection()->insertOnDuplicate(
                $this->getMainTable(),
                $data,
                [
                    JobReplacingInterface::JOB_ID,
                    JobReplacingInterface::ATTRIBUTE_CODE,
                    JobReplacingInterface::ENTITY_TYPE
                ]
            );
        }
    }
}
