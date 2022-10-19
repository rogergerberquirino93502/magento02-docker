<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Api\Import;

use Firebear\ImportExport\Api\Data\ImportInterface;
use Magento\Framework\Exception\CouldNotDeleteException;

/**
 * Delete job command (Service Provider Interface - SPI)
 *
 * @api
 */
interface DeleteInterface
{
    /**
     * Delete job
     *
     * @param ImportInterface $job
     * @return \Firebear\ImportExport\Api\Data\ImportInterface
     * @throws CouldNotDeleteException
     */
    public function execute(ImportInterface $job);
}
