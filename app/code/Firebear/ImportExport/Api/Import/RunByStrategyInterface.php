<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Api\Import;

use Firebear\ImportExport\Api\Data\ImportInterface;

/**
 * Run jobs by strategy (Service Provider Interface - SPI)
 *
 * @api
 */
interface RunByStrategyInterface
{
    /**
     * Run job by strategy
     *
     * @param ImportInterface $job
     * @param string $type
     * @return bool
     */
    public function execute(ImportInterface $job, $type = 'webapi');
}
