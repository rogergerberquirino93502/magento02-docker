<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Lib;

/**
 * Install lib pool interface
 */
interface LibPoolInterface
{
    /**
     * Retrieve registered libs
     *
     * @return LibInterface[]
     */
    public function get();
}
