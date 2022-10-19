<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Api\Import;

/**
 * Run chain jobs (Service Provider Interface - SPI)
 *
 * @api
 */
interface RunChainInterface
{
    /**
     * Run chain jobs
     *
     * @param string $type
     * @return bool
     */
    public function execute($type = 'webapi');
}
