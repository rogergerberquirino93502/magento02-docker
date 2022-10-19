<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Api\Export\History;

use Firebear\ImportExport\Api\Data\ExportHistoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * GetById command (Service Provider Interface - SPI)
 *
 * @api
 */
interface GetByIdInterface
{
    /**
     * Execute command
     *
     * @param int $historyId
     * @return ExportHistoryInterface
     * @throws NoSuchEntityException
     */
    public function execute($historyId);
}
