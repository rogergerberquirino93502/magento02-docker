<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Filesystem;

use Firebear\ImportExport\Model\Filesystem\Driver\Http;

/**
 * A pool of stream wrappers
 */
class DriverPool extends \Magento\Framework\Filesystem\DriverPool
{
    /**
     * DriverPool constructor.
     * @param array $extraTypes
     */
    public function __construct($extraTypes = [])
    {
        $this->types[self::HTTP] = Http::class;
        parent::__construct($extraTypes);
    }
}
