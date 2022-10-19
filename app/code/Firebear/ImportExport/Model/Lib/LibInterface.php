<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Lib;

/**
 * Install lib interface
 */
interface LibInterface
{
    /**
     * Retrieve message
     *
     * @return string
     */
    public function getMessage();

    /**
     * Check whether the extension is allowed
     *
     * @param string $extension
     * @return bool
     */
    public function isAvailable($extension);

    /**
     * Check whether lib is installed
     *
     * @return bool
     */
    public function isInstalled();
}
