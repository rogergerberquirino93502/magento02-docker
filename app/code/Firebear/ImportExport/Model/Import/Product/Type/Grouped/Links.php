<?php
/**
 * @copyright: Copyright © 2018 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Product\Type\Grouped;

use Magento\Framework\App\ResourceConnection;

/**
 * Class Downloadable
 */
class Links extends \Magento\GroupedImportExport\Model\Import\Product\Type\Grouped\Links
{

    protected $fireImportFactory;
    /** @var \Firebear\ImportExport\Api\JobRepositoryInterface  */
    protected $importJobRepository;

    /**
     * Links constructor.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Link $productLink
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\ImportExport\Model\ImportFactory $importFactory
     * @param \Firebear\ImportExport\Model\ImportFactory $fireImportFactory
     * @param \Firebear\ImportExport\Api\JobRepositoryInterface $importJobRepository
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\Link $productLink,
        ResourceConnection $resource,
        \Magento\ImportExport\Model\ImportFactory $importFactory,
        \Firebear\ImportExport\Model\ImportFactory $fireImportFactory,
        \Firebear\ImportExport\Api\JobRepositoryInterface $importJobRepository
    ) {
        parent::__construct($productLink, $resource, $importFactory);
        $this->fireImportFactory = $fireImportFactory;
        $this->importJobRepository = $importJobRepository;
    }

    /**
     * @return string
     */
    protected function getBehavior()
    {
        if ($this->behavior === null) {
            $this->behavior = $this->fireImportFactory->create()->getFireDataSourceModel()->getBehavior();
        }

        return $this->behavior;
    }

    /**
     * @param array $productIds
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function deleteOldLinks($productIds)
    {
        $jobId = $this->fireImportFactory->create()->getFireDataSourceModel()->getJobId();
        $importJobData = $this->importJobRepository->getById($jobId);
        $sourceData = $importJobData->getSourceData();
        $relationTable = $this->productLink->getTable('catalog_product_relation');
        $this->behavior = $importJobData->getBehaviorData()['behavior']
            ?? \Magento\ImportExport\Model\Import\AbstractEntity::getDefaultBehavior();
        if ($this->behavior != \Magento\ImportExport\Model\Import::BEHAVIOR_APPEND
            || (isset($sourceData['remove_product_association']) && $sourceData['remove_product_association'] == 1)
        ) {
            $this->connection->delete(
                $this->productLink->getMainTable(),
                $this->connection->quoteInto(
                    'product_id IN (?) AND link_type_id = ' . $this->getLinkTypeId(),
                    $productIds
                )
            );
            // Remove Product Relations form catalog_product_relation
            $this->connection->delete(
                $relationTable,
                $this->connection->quoteInto(
                    'parent_id IN (?)',
                    $productIds
                )
            );
        }
    }
}
