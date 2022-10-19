<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Api\Import;

/**
 * Mass update jobs by ids (Service Provider Interface - SPI)
 *
 * @api
 */
interface MassUpdateInterface
{
    /**
     * Run jobs by ids
     *
     * @param int[] $jobIds
     * @param string $field
     * @param mixed $value
     * @return int
     */
    public function execute(array $jobIds, string $field, $value);
}
