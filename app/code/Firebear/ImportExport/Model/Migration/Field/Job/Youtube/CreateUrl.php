<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration\Field\Job\Youtube;

use Firebear\ImportExport\Model\Migration\Field\JobInterface;

/**
 * @inheritdoc
 */
class CreateUrl implements JobInterface
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
        return "https://www.youtube.com/watch?v={$sourceValue}";
    }
}
