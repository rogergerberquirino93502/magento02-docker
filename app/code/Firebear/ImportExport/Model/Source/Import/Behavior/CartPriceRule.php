<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Import\Behavior;

/**
 * Class CartPriceRule
 *
 * @package Firebear\ImportExport\Model\Source\Import\Behavior
 */
class CartPriceRule extends \Magento\ImportExport\Model\Source\Import\AbstractBehavior
{
    const  ONLY_UPDATE = 'update';

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            \Magento\ImportExport\Model\Import::BEHAVIOR_APPEND => __('Add/Update')
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return 'cart_price_rule';
    }
}
