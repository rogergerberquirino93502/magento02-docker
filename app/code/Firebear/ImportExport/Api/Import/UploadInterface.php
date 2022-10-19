<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Api\Import;

use Magento\Framework\Exception\LocalizedException;

/**
 * File upload command (Service Provider Interface - SPI)
 *
 * @api
 */
interface UploadInterface
{
    /**
     * Upload file
     *
     * @param string $fileName
     * @param bool $uniqueName
     * @return string
     * @throws LocalizedException
     */
    public function execute($fileName = '', $uniqueName = false);
}
