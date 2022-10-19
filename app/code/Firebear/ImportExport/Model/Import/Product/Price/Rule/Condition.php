<?php
/**
 * @copyright: Copyright © 2018 Firebear Studio GmbH. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Import\Product\Price\Rule;

use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogRule\Model\Rule\Condition\ProductFactory as ConditionFactory;
use Magento\CatalogRule\Model\Rule\Condition\Product as ProductCondition;
use Magento\Rule\Model\Condition\Context;
use Magento\Rule\Model\Condition\Combine;

/**
 * Class Condition
 */
class Condition extends Combine
{
    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var ConditionFactory
     */
    private $conditionFactory;

    /**
     * @var mixed[]
     */
    private $attributes;

    /**
     * @var string[]
     */
    private $multiselect;

    /**
     * Condition constructor
     *
     * @param Context $context
     * @param ProductFactory $productFactory
     * @param ConditionFactory $conditionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        ProductFactory $productFactory,
        ConditionFactory $conditionFactory,
        array $data = []
    ) {
        $this->productFactory = $productFactory;
        $this->conditionFactory = $conditionFactory;

        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * @param $data
     * @return bool
     */
    public function validatePriceRuleConditions($data)
    {
        $this->setPrefix('conditions');
        $conditions = $this->convertFlatToRecursive($data['conditions']);
        $this->loadArray($conditions['conditions'][1] ?? []);

        $rowData = $data['row'];
        $rowData['categories'] = $data['categories'];
        $rowData['attribute_set_id'] = $data['attribute_set_id'];
        foreach ($rowData as $attribute => $value) {
            $rowData[$attribute] = $this->prepareValue($attribute, $value);
        }

        $product = $this->productFactory->create();
        $product->setData($rowData);
        $product->setStoreId($data['store_id']);

        return $this->validate($product);
    }

    /**
     * Set specified data to current rule
     * Set conditions recursively
     *
     * @param mixed[] $data
     * @return mixed[]
     */
    private function convertFlatToRecursive(array $data)
    {
        $conditions = [];
        foreach ($data as $key => $value) {
            if ($key === 'conditions' && is_array($value)) {
                foreach ($value as $id => $data) {
                    $path = explode('--', $id);
                    $node = & $conditions;
                    for ($i = 0, $l = count($path); $i < $l; $i++) {
                        if (!isset($node[$key][$path[$i]])) {
                            $node[$key][$path[$i]] = [];
                        }
                        $node = & $node[$key][$path[$i]];
                    }
                    foreach ($data as $k => $v) {
                        $node[$k] = ($k == 'attribute' && $v == 'category_ids') ? 'categories' : $v;
                    }
                }
            }
        }
        return $conditions;
    }

    /**
     * Prepare attribute value
     *
     * @param string $attribute
     * @param string $value
     * @return string
     */
    private function prepareValue($attribute, $value)
    {
        if (null === $this->attributes) {
            $condition = $this->conditionFactory->create();
            $attributes = $condition->getAttributeOption();

            foreach ($attributes as $code => $label) {
                $condition = $this->conditionFactory->create();
                $condition->setAttribute($code);

                $type = $condition->getValueElementType();
                if (in_array($type, ['select', 'multiselect'])) {
                    $this->attributes[$code] = [];
                    if ('multiselect' == $type) {
                        $this->multiselect[$code] = true;
                    }
                    foreach ($condition->getValueSelectOptions() as $option) {
                        if ('' === $option['value']) {
                            continue;
                        }
                        $label = (string)$option['label'];
                        $this->attributes[$code][$label] = $option['value'];
                    }
                }
            }
        }
        if (!empty($this->multiselect[$attribute])) {
            $result = [];
            $delimiter = strpos($value, '|') !== false ? '|' : ',';
            $parts = explode($delimiter, $value);
            foreach ($parts as $key => $part) {
                $value = $this->attributes[$attribute][$part] ?? $part;
                if (!empty($value)) {
                    $parts[$key] = $value;
                }
            }
            return $parts;
        }
        return $this->attributes[$attribute][$value] ?? $value;
    }
}
