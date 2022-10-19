<?php

/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export\Customer;

/**
 * Class Additional
 *
 * @package Firebear\ImportExport\Model\Export\Customer
 */
class Additional
{
    /**
     * @var array
     */
    public $fields = ['store_id'];

    /**
     * @var array
     */
    protected $convFields = [
        'store_id' => 'store_id'
    ];

    protected $store;

    /**
     * Additional constructor.
     * @param \ \Magento\Store\Model\StoreManager $store
     */
    public function __construct(
        \Magento\Store\Model\StoreManager $store
    ) {
        $this->store = $store;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $option = [];
        $option[] = ['label' => __('Store'), 'value' => 'store_id'];

        return $option;
    }

    public function getAdditionalFields()
    {
        $option = [];
        $stores = [];
        foreach ($this->store->getStores() as $id => $store) {
            $stores[] = ['label' => $store->getName(), 'value' => $id];
        }
        $option[] = [
            'field' => 'store_id',
            'type' => 'select',
            'select' => $stores
        ];

        return $option;
    }

    /**
     * @param $field
     * @return bool|mixed
     */
    public function convertFields($field)
    {
        if (isset($this->convFields[$field])) {
            return $this->convFields[$field];
        }

        return false;
    }
}
