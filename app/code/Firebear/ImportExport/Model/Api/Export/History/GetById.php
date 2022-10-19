<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Api\Export\History;

use Firebear\ImportExport\Api\Export\History\GetByIdInterface;
use Firebear\ImportExport\Api\Data\ExportHistoryInterface;
use Firebear\ImportExport\Model\Export\HistoryFactory;
use Firebear\ImportExport\Model\ResourceModel\Export\History as HistoryResource;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * GetById command (Service Provider Interface - SPI)
 *
 * @api
 */
class GetById implements GetByIdInterface
{
    /**
     * @var HistoryFactory
     */
    private $historyFactory;

    /**
     * @var HistoryResource
     */
    private $resource;

    /**
     * Initialize command
     *
     * @param HistoryFactory $historyFactory
     * @param HistoryResource $resource
     */
    public function __construct(
        HistoryFactory $historyFactory,
        HistoryResource $resource
    ) {
        $this->historyFactory = $historyFactory;
        $this->resource = $resource;
    }

    /**
     * Execute command
     *
     * @param int $historyId
     * @return ExportHistoryInterface
     * @throws NoSuchEntityException
     */
    public function execute($historyId)
    {
        $history = $this->historyFactory->create();
        $this->resource->load($history, $historyId);

        if (!$history->getId()) {
            throw new NoSuchEntityException(
                __('Export History with id "%1" does not exist.', $historyId)
            );
        }
        return $history;
    }
}
