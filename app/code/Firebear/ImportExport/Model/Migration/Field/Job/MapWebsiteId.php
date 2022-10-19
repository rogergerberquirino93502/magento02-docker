<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration\Field\Job;

use Magento\Framework\Exception\LocalizedException;

/**
 * @inheritdoc
 */
class MapWebsiteId extends MapValue
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
        try {
            return parent::job($sourceField, $sourceValue, $destinationFiled, $destinationValue, $sourceDataRow);
        } catch (LocalizedException $e) {
            throw new LocalizedException(__("Mapping not found for website id %1.", $sourceValue));
        }
    }
}
