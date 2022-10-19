<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Platform;

/**
 * Platform interface
 */
interface PlatformInterface
{
    /**
     * Check if platform is gateway
     *
     * @return bool
     */
    public function isGateway();

    /**
     * Delete columns before replace values
     *
     * @param $data
     * @return array
     * @see \Firebear\ImportExport\Traits\Import\Map
     */
    public function deleteColumns($data);

    /**
     * Prepare columns before replace columns
     *
     * @param $data
     * @return array
     * @see \Firebear\ImportExport\Traits\Import\Map
     */
    public function prepareColumns($data);

    /**
     * Post prepare columns after replace columns
     *
     * @param $data
     * @param $maps
     * @return array
     * @see \Firebear\ImportExport\Traits\Import\Map
     */
    public function afterColumns($data, $maps);

    /**
     * Prepare row
     *
     * @param $data
     * @return array
     * @see \Firebear\ImportExport\Model\Source\Platform\AbstractPlatform
     */
    public function prepareRow($data);
}
