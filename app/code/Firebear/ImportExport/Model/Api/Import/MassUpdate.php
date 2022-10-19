<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Api\Import;

use Firebear\ImportExport\Api\Data\ImportInterface;
use Firebear\ImportExport\Api\Import\MassUpdateInterface;
use Firebear\ImportExport\Model\ResourceModel\Job\CollectionFactory;

/**
 * Mass update jobs by ids (Service Provider Interface - SPI)
 *
 * @api
 */
class MassUpdate implements MassUpdateInterface
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * Initialize
     *
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Run jobs by ids
     *
     * @param int[] $jobIds
     * @param string $field
     * @param mixed $value
     * @return int
     */
    public function execute(array $jobIds, string $field, $value)
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(ImportInterface::ENTITY_ID, ['in' => $jobIds]);

        $totalRecords = $collection->getSize();
        if ($totalRecords) {
            $collection->setDataToAll($field, $value);
            $collection->walk('save');
        }
        return $totalRecords;
    }
}
