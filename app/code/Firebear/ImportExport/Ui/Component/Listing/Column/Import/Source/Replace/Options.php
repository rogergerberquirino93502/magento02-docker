<?php
/**
 * Options
 *
 * @copyright Copyright © 2018 Firebear Studio. All rights reserved.
 * @author    Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Replace;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Options
 * @package Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Replace
 */
class Options implements OptionSourceInterface
{

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        return [
            [
                'label' => __('Empty rows'),
                'value' => 0
            ],
            [
                'label' => __('All rows'),
                'value' => 1
            ]
        ];
    }
}
