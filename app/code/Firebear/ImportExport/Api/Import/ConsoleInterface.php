<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Api\Import;

/**
 * Console command (Service Provider Interface - SPI)
 *
 * @api
 */
interface ConsoleInterface
{
    /**
     * Get console info
     *
     * @param string $file
     * @param int $counter
     * @return string
     */
    public function execute($file, $counter);
}
