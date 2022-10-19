<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Api\Import;

/**
 * Job before run command (Service Provider Interface - SPI)
 *
 * @api
 */
interface BeforeRunInterface
{
    /**
     * Retrieve file name
     *
     * @param int $jobId
     * @return string
     */
    public function execute($jobId);
}
