<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Api\Export\History;

/**
 * Compress command (Service Provider Interface - SPI)
 *
 * @api
 */
interface CompressInterface
{
    /**
     * Execute command
     *
     * @param string $path
     * @return string
     */
    public function execute($path);
}
