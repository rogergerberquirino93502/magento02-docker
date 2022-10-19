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
class Scope implements FilterProcessorInterface
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
            $column = explode(':', $columnName);
            foreach ($value as $key => $val) {
                if (is_numeric($val) && !empty($column[$key])) {
                    $collection->addFieldToFilter($column[$key], ['eq' => $val]);
                }
            }
        }
    }
}
