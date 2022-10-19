<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration\Field;

use Magento\Framework\Exception\LocalizedException;

/**
 * @api
 */
interface JobInterface
{
    /**
     * @param mixed $sourceField
     * @param mixed $sourceValue
     * @param mixed $destinationFiled
     * @param mixed $destinationValue
     * @param array $sourceDataRow
     *
     * @throws LocalizedException
     *
     * @return mixed
     */
    public function job($sourceField, $sourceValue, $destinationFiled, $destinationValue, $sourceDataRow);
}
