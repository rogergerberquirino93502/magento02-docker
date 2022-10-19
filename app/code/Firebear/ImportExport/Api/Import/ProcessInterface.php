<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Api\Import;

/**
 * Job process command (Service Provider Interface - SPI)
 *
 * @api
 */
interface ProcessInterface
{
    /**
     * Process import
     *
     * @param int $jobId
     * @param string $file
     * @param int $offset
     * @param string $error
     * @return \Firebear\ImportExport\Api\Import\ProcessResponseInterface
     */
    public function execute($jobId, $file, $offset, $error);
}
