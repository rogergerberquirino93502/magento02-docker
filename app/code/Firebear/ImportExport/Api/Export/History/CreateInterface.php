<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Api\Export\History;

use Firebear\ImportExport\Api\Data\ExportHistoryInterface;

/**
 * Create command (Service Provider Interface - SPI)
 *
 * @api
 */
interface CreateInterface
{
    /**
     * Create history
     *
     * @param int $id
     * @param string $file
     * @param string $type
     * @return ExportHistoryInterface
     */
    public function execute($id, $file, $type);
}
