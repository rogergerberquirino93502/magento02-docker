<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Api\Import;

/**
 * Job get bunch count command (Service Provider Interface - SPI)
 *
 * @api
 */
interface GetBunchCountInterface
{
    /**
     * Get job bunch count
     *
     * @param int $jobId
     * @param string $file
     * @return int
     */
    public function execute($jobId, $file);
}
