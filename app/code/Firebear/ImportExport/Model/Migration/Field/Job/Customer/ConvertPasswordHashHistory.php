<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration\Field\Job\Customer;

use Firebear\ImportExport\Model\Migration\Field\JobInterface;

/**
 * @inheritdoc
 */
class ConvertPasswordHashHistory implements JobInterface
{
    /**
     * @inheritdoc
     */
    public function job(
        $sourceField,
        $sourceValue,
        $destinationFiled,
        $destinationValue,
        $sourceDataRow
    ) {
        if (empty($sourceValue)) {
            return $sourceValue;
        }

        $hashes = json_decode($sourceValue, true);
        $hashes = array_map(function ($hash) {
            return "{$hash}:0";
        }, $hashes);

        return join(',', $hashes);
    }
}
