<?php
/**
 * @copyright: Copyright © 2018 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\ResourceModel\Sales\Order\Tax\Item;

use Magento\Sales\Model\ResourceModel\Collection\AbstractCollection;
use Magento\Sales\Model\Order\Tax\Item;
use Magento\Sales\Model\ResourceModel\Order\Tax\Item as ItemResource;

/**
 * Order Tax Item Collection
 */
class Collection extends AbstractCollection
{
    /**
     * Model Initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Item::class, ItemResource::class);
    }
}
