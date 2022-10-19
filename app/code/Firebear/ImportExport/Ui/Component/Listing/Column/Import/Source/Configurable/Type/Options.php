<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Configurable\Type;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Options
 */
class Options implements OptionSourceInterface
{
    const FIELD = 'field';

    const PART_UP = 'part_up';

    const PART_DOWN = 'part_down';

    const SUB_UP = 'sub_up';

    const SUB_DOWN = 'sub_down';

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'label' => __('Сreate config product by same attribute of simple products'),
                'value' => self::FIELD
            ],
            [
                'label' => __('Create configurable products by custom rules (part of a line before delimiter)'),
                'value' => self::PART_UP
            ],
            [
                'label' => __('Create configurable products by custom rules (part of a line after delimiter)'),
                'value' => self::PART_DOWN
            ],
            [
                'label' => __(
                    'Create configurable products by custom rules '.
                    '(the number of characters from the beginning of the line)'
                ),
                'value' => self::SUB_UP
            ],
            [
                'label' => __(
                    'Create configurable products by custom rules '.
                    '(the number of characters from the ending of the line)'
                ),
                'value' => self::SUB_DOWN
            ]
        ];
    }
}
