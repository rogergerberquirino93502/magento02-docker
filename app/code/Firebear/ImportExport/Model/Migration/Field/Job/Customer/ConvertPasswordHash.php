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
class ConvertPasswordHash implements JobInterface
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
        return "{$sourceValue}:0";
    }
}
