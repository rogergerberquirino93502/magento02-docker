<?php
/**
 * @copyright: Copyright © 2018 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Import\Behavior;

use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Source\Import\AbstractBehavior;
use Magento\Sales\Model\Order as OrderModel;

/**
 * Order Behavior Import
 *
 */
class Order extends AbstractBehavior
{
    /**
     * Get Array Of Possible Values
     *
     * @return array
     */
    public function toArray()
    {
        return [
            Import::BEHAVIOR_ADD_UPDATE => __('Add/Update'),
            Import::BEHAVIOR_REPLACE => __('Replace'),
            Import::BEHAVIOR_DELETE => __('Delete')
        ];
    }

    /**
     * Get Current Behaviour Group Code
     *
     * @return string
     */
    public function getCode()
    {
        return OrderModel::ENTITY;
    }
}
