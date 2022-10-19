<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import;

use Firebear\ImportExport\Model\Source\Platform\AbstractPlatform;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Directory\Write;
use Magento\ImportExport\Model\Import\AbstractSource;

/**
 * Class Adapter
 *
 * @package Firebear\ImportExport\Model\Import
 */
class Adapter
{

    /**
     * Create adapter instance for specified source file
     *
     * @param string $class
     * @param string $source Source file path
     * @param Write $directory
     * @param AbstractPlatform $platform
     * @param mixed $data OPTIONAL Adapter constructor options
     * @return AbstractSource
     */
    public static function findAdapterFor($class, $source, $directory, $platform = null, $data = [])
    {
        return self::factory($class, $source, $directory, $platform, $data);
    }

    /**
     * Adapter factory. Checks for availability, loads and create instance of import adapter object
     *
     * @param string $class
     * @param string $source Source file path
     * @param Write $directory
     * @param AbstractPlatform $platform
     * @param mixed $data OPTIONAL Adapter constructor options
     * @return AbstractSource
     * @throws LocalizedException
     */
    public static function factory($class, $source, $directory, $platform = null, $data = [])
    {
        if (!class_exists($class)) {
            throw new LocalizedException(
                __('\'%1\' model extension is not supported', $class)
            );
        }

        $objectManger = ObjectManager::getInstance();

        $adapter = $objectManger
            ->create(
                $class,
                [
                    'file' => $source,
                    'directory' => $directory,
                    'platform' => $platform,
                    'data' => $data
                ]
            );
        if (!$adapter instanceof AbstractSource) {
            throw new LocalizedException(
                __('Adapter must be an instance of \Magento\ImportExport\Model\Import\AbstractSource')
            );
        }
        return $adapter;
    }
}
