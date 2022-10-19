<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

declare(strict_types=1);

namespace Firebear\ImportExport\Model\Export\FilterProcessor\Filter;

use Firebear\ImportExport\Model\Export\FilterProcessor\FilterProcessorInterface;
use Magento\Framework\Data\Collection;
use Magento\Framework\Exception\LocalizedException;

/**
 * @inheritdoc
 */
class Number implements FilterProcessorInterface
{
    /**
     * @param Collection $collection
     * @param string $columnName
     * @param array|string $value
     * @return void
     * @throws LocalizedException
     */
    public function process(Collection $collection, string $columnName, $value)
    {
        if (is_array($value)) {
            $from = $value[0] ?? null;
            $to = $value[1] ?? null;

            if (is_numeric($from) && !empty($from)) {
                $collection->addFieldToFilter($columnName, ['from' => $from]);
            }

            if (is_numeric($to) && !empty($to)) {
                $collection->addFieldToFilter($columnName, ['to' => $to]);
            }
        } elseif (is_numeric($value) && !empty($value)) {
            $collection->addFieldToFilter($columnName, ['eq' => $value]);
        }
    }
}
