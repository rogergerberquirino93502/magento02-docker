<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import;

/**
 * Import Adapter Interface
 *
 */
interface ImportAdapterInterface
{
    /**
     * Retrieve All Fields Source
     *
     * @return array
     */
    public function getAllFields();
}
