<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Api\Import;

/**
 * Run job by id (Service Provider Interface - SPI)
 *
 * @api
 */
interface RunByIdInterface
{
    /**
     * Run job by id
     *
     * @param int $jobId
     * @param string $type
     * @return bool
     */
    public function execute($jobId, $type = 'webapi');
}
