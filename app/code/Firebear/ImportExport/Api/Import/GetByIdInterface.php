<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Api\Import;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Get job by id command (Service Provider Interface - SPI)
 *
 * @api
 */
interface GetByIdInterface
{
    /**
     * Get job by id
     *
     * @param int $jobId
     * @return \Firebear\ImportExport\Api\Data\ImportInterface
     * @throws NoSuchEntityException
     */
    public function execute($jobId);
}
