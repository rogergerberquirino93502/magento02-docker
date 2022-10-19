<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Filesystem\File;

use Firebear\ImportExport\Model\Filesystem\DriverPool;

/**
 * Class ReadFactory
 *
 * @package Firebear\ImportExport\Model\Filesystem\File
 */
class ReadFactory
{
    /**
     * @var DriverPool
     */
    private $driverPool;

    /**
     * ReadFactory constructor.
     * @param DriverPool $driverPool
     */
    public function __construct(DriverPool $driverPool)
    {
        $this->driverPool = $driverPool;
    }

    /**
     * @param $path
     * @param $driver
     * @return \Magento\Framework\Filesystem\File\Read
     */
    public function create($path, $driver)
    {
        if (is_string($driver)) {
            return new \Magento\Framework\Filesystem\File\Read($path, $this->driverPool->getDriver($driver));
        }

        return new \Magento\Framework\Filesystem\File\Read($path, $driver);
    }
}
