<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Import\Behavior;

use Magento\ImportExport\Model\Source\Import\AbstractBehavior;
use Magento\ImportExport\Model\Import;

/**
 * Class Product
 *
 * @package Firebear\ImportExport\Model\Source\Import\Behavior
 */
class Product extends AbstractBehavior
{
    const  ONLY_UPDATE = 'update';
    const  ONLY_ADD = 'add';

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            Import::BEHAVIOR_APPEND => __('Add/Update'),
            self::ONLY_UPDATE => __('Only Update'),
            self::ONLY_ADD => __('Only Add'),
            Import::BEHAVIOR_REPLACE => __('Replace'),
            Import::BEHAVIOR_DELETE => __('Delete')
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return 'product';
    }

    /**
     * {@inheritdoc}
     */
    public function getNotes($entityCode)
    {
        $messages = ['catalog_product' => [
            Import::BEHAVIOR_REPLACE => __("Note: Product IDs will be regenerated.")
        ]];
        return isset($messages[$entityCode]) ? $messages[$entityCode] : [];
    }
}
