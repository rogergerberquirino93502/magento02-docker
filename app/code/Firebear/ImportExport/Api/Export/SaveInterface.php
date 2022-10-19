<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Api\Export;

use Firebear\ImportExport\Api\Data\ExportInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Validation\ValidationException;

/**
 * Save command (Service Provider Interface - SPI)
 *
 * @api
 */
interface SaveInterface
{
    /**
     * Save job
     *
     * @param ExportInterface $job
     * @return ExportInterface
     * @throws CouldNotSaveException
     * @throws ValidationException
     */
    public function execute(ExportInterface $job);
}
