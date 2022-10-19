<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Api\Import;

use Firebear\ImportExport\Api\Data\ImportInterface;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Save job data command (Service Provider Interface - SPI)
 *
 * @api
 */
interface SaveInterface
{
    /**
     * Save job
     *
     * @param ImportInterface $job
     * @return \Firebear\ImportExport\Api\Data\ImportInterface
     * @throws CouldNotSaveException
     */
    public function execute(ImportInterface $job);
}
