<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Api\Export\History;

use Firebear\ImportExport\Api\Data\ExportHistoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Save command (Service Provider Interface - SPI)
 *
 * @api
 */
interface SaveInterface
{
    /**
     * Save history
     *
     * @param ExportHistoryInterface $history
     * @return ExportHistoryInterface
     * @throws CouldNotSaveException
     */
    public function execute(ExportHistoryInterface $history);
}
