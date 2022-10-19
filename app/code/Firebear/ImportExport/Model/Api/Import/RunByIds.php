<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Api\Import;

use Firebear\ImportExport\Api\Data\ImportInterface;
use Firebear\ImportExport\Api\Import\RunByStrategyInterface;
use Firebear\ImportExport\Api\Import\RunByIdsInterface;
use Firebear\ImportExport\Model\ResourceModel\Job\CollectionFactory;

/**
 * Run jobs by ids (Service Provider Interface - SPI)
 *
 * @api
 */
class RunByIds implements RunByIdsInterface
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var RunByStrategyInterface
     */
    private $runByStrategy;

    /**
     * Initialize
     *
     * @param CollectionFactory $collectionFactory
     * @param RunByStrategyInterface $runByStrategy
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        RunByStrategyInterface $runByStrategy
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->runByStrategy = $runByStrategy;
    }

    /**
     * Run jobs by ids
     *
     * @param int[] $jobIds
     * @param string $type
     * @return bool
     */
    public function execute(array $jobIds, $type = 'webapi')
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(ImportInterface::IS_ACTIVE, ImportInterface::STATUS_ENABLED);

        if ($jobIds) {
            $collection->addFieldToFilter(ImportInterface::ENTITY_ID, ['in' => $jobIds]);
        }

        if ($collection->getSize()) {
            foreach ($collection as $job) {
                $this->runByStrategy->execute($job, $type);
            }
            return true;
        }
        return false;
    }
}
