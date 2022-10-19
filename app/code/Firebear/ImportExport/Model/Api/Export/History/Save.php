<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Api\Export\History;

use Firebear\ImportExport\Model\ResourceModel\Export\History as HistoryResource;
use Firebear\ImportExport\Api\Export\History\SaveInterface;
use Firebear\ImportExport\Api\Data\ExportHistoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Save command (Service Provider Interface - SPI)
 *
 * @api
 */
class Save implements SaveInterface
{
    /**
     * @var HistoryResource
     */
    private $resource;

    /**
     * Initialize command
     *
     * @param HistoryResource $resource
     */
    public function __construct(
        HistoryResource $resource
    ) {
        $this->resource = $resource;
    }

    /**
     * Save history
     *
     * @param ExportHistoryInterface $history
     * @return ExportHistoryInterface
     * @throws CouldNotSaveException
     */
    public function execute(ExportHistoryInterface $history)
    {
        try {
            $this->resource->save($history);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the history: %1',
                $exception->getMessage()
            ));
        }
        return $history;
    }
}
