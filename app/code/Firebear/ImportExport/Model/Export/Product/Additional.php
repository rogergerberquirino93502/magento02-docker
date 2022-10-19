<?php

/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export\Product;

/**
 * Class Additional
 *
 * @package Firebear\ImportExport\Model\Export\Product
 */
class Additional
{
    /**
     * @var \Magento\Catalog\Model\Product\Type
     */
    protected $type;

    /**
     * @var \Magento\Catalog\Model\Product\AttributeSet\Options
     */
    protected $options;

    /**
     * @var array
     */
    public $fields = [
        'product_type',
        'attribute_set_code',
        'store',
        'tier_prices:fixed',
        'tier_prices:quantity',
        'tier_prices:discount'
    ];

    /**
     * @var array
     */
    protected $convFields = [
        'product_type' => 'type_id',
        'attribute_set_code' => 'attribute_set_id',
        'store' => 'store',
        'tier_prices:fixed' => 'tp.value',
        'tier_prices:quantity' => 'tp.qty',
        'tier_prices:discount' => 'tp.percentage_value'
    ];

    /**
     * \Magento\Store\Model\StoreManager
     *
     */
    protected $store;

    /**
     * Additional constructor.
     * @param \Magento\Catalog\Model\Product\Type $type
     * @param \Magento\Catalog\Model\Product\AttributeSet\Options $options
     * @param \Magento\Store\Model\StoreManager $store
     */
    public function __construct(
        \Magento\Catalog\Model\Product\Type $type,
        \Magento\Catalog\Model\Product\AttributeSet\Options $options,
        \Magento\Store\Model\StoreManager $store
    ) {
        $this->type = $type;
        $this->options = $options;
        $this->store = $store;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $option = [
            ['label' => __('Product Type'), 'value' => 'product_type'],
            ['label' => __('Attribute Set'), 'value' => 'attribute_set_code'],
            ['label' => __('Store'), 'value' => 'store'],
            ['label' => __('Tier Prices: Quantity'), 'value' => 'tier_prices:quantity'],
            ['label' => __('Tier Prices: Fixed Price'), 'value' => 'tier_prices:fixed'],
            ['label' => __('Tier Prices: Discount'), 'value' => 'tier_prices:discount'],
        ];
        return $option;
    }

    public function getAdditionalFields()
    {
        $option = [];
        $types = [];
        foreach ($this->type->getOptionArray() as $key => $item) {
            $types[] = ['label' => $item, 'value' => $key];
        }
        $option[] = [
            'field' => 'product_type',
            'type' => 'select',
            'select' => $types
        ];

        $option[] = [
            'field' => 'attribute_set_code',
            'type' => 'select',
            'select' => $this->options->toOptionArray()
        ];
        $stores = [];
        foreach ($this->store->getStores() as $id => $store) {
            $stores[] = ['label' => $store->getName(), 'value' => $id];
        }
        $option[] = [
            'field' => 'store',
            'type' => 'select',
            'select' => $stores
        ];
        $option[] = [
            'field' => 'tier_prices:fixed',
            'type' => 'text'
        ];
        $option[] = [
            'field' => 'tier_prices:quantity',
            'type' => 'text'
        ];
        $option[] = [
            'field' => 'tier_prices:discount',
            'type' => 'text'
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
