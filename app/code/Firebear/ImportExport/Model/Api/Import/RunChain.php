<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Api\Import;

use Magento\Framework\Data\Collection;
use Firebear\ImportExport\Api\Data\ImportInterface;
use Firebear\ImportExport\Api\Import\RunByStrategyInterface;
use Firebear\ImportExport\Api\Import\RunChainInterface;
use Firebear\ImportExport\Model\ResourceModel\Job\CollectionFactory;

/**
 * Run chain jobs (Service Provider Interface - SPI)
 *
 * @api
 */
class RunChain implements RunChainInterface
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
     * Run chain jobs
     *
     * @param string $type
     * @return bool
     */
    public function execute($type = 'webapi')
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(ImportInterface::IS_ACTIVE, ImportInterface::STATUS_ENABLED);
        $collection->addFieldToFilter(ImportInterface::POSITION, ['notnull' => true]);

        $collection->setOrder(ImportInterface::POSITION, Collection::SORT_ORDER_ASC);

        if ($collection->getSize()) {
            foreach ($collection as $job) {
                if (false === $this->runByStrategy->execute($job, $type)) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }
}
