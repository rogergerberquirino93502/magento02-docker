<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Source\Config;

use Magento\Framework\Option\ArrayInterface;

/**
 * Email type source
 */
class EmailType implements ArrayInterface
{
    /**
     * Retrieve options as array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 0, 'label' => __('None')],
            ['value' => 1, 'label' => __('Failed Jobs')],
            ['value' => 2, 'label' => __('Successful Jobs')],
            ['value' => 3, 'label' => __('Failed and Successful Jobs')]
        ];
    }

    /**
     * Retrieve options in key-value format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            0 =>  __('None'),
            1 => __('Failed Jobs'),
            2 => __('Successful Jobs'),
            3 => __('Failed and Successful Jobs')
        ];
    }
}
