<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration\Field\Job\ColorSwatch;

use Firebear\ImportExport\Model\Migration\Field\JobInterface;

/**
 * @inheritdoc
 */
class GenerateImagePath implements JobInterface
{
    /**
     * @inheritdoc
     */
    public function job($sourceField, $sourceValue, $destinationFiled, $destinationValue, $sourceDataRow)
    {
        return "/$sourceValue[0]/$sourceValue[1]/$sourceValue";
    }
}
