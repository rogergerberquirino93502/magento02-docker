<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Helper;

use OpenSpout\Reader\ReaderInterface;
use Magento\Framework\App\Helper\AbstractHelper;

/**
 * Spout helper
 */
class Spout extends AbstractHelper
{
    /**
     * Check whether spout is install
     *
     * @return bool
     */
    public function isSpoutInstall()
    {
        return interface_exists(ReaderInterface::class);
    }

    /**
     * Check whether name is allow
     *
     * @param string $name
     * @return bool
     */
    public function isAllowName($name)
    {
        $names = [];
        return !in_array($name, $names) || $this->isSpoutInstall();
    }
}
