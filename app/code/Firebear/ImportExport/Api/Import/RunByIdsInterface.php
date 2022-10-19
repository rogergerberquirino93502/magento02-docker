<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Api\Import;

/**
 * Run jobs by ids (Service Provider Interface - SPI)
 *
 * @api
 */
interface RunByIdsInterface
{
    /**
     * Run jobs by ids
     *
     * @param int[] $jobIds
     * @param string $type
     * @return bool
     */
    public function execute(array $jobIds, $type = 'webapi');
}
