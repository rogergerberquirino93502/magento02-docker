<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Api\Import;

/**
 * Reindex command (Service Provider Interface - SPI)
 *
 * @api
 */
interface ReindexInterface
{
    /**
     * Reindex
     *
     * @param int $jobId
     * @param string $file
     * @return bool
     */
    public function execute($jobId, $file);
}
