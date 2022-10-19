<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Traits\Import\Product;

use Firebear\ImportExport\Model\Import\Product;
use Magento\Downloadable\Model\Product\Type as DownloadableProductType;
use Magento\ImportExport\Model\Import;

trait Type
{
    /**
     * Attach Attributes By Id
     *
     * @param string $attributeSetName
     * @param array $attributeIds
     *
     * @return void
     */
    protected function attachAttributesById($attributeSetName, $attributeIds)
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute */
        foreach ($this->_prodAttrColFac->create()->addFieldToFilter(
            ['main_table.attribute_id', 'main_table.attribute_code'],
            [
                ['in' => $attributeIds],
                ['in' => $this->_forcedAttributesCodes],
            ]
        ) as $attribute) {
            $attributeId = $attribute->getId();
            $attributeCode = $attribute->getAttributeCode();

            if ($attribute->getIsVisible() || in_array($attributeCode, $this->_forcedAttributesCodes)) {
                if (!isset(self::$commonAttributesCache[$attributeId])) {
                    $options = $this->_entityModel->getAttributeOptions(
                        $attribute,
                        $this->_indexValueAttributes
                    );
                    self::$commonAttributesCache[$attributeId] = [
                        'id' => $attributeId,
                        'code' => $attributeCode,
                        'is_user_defined' => $attribute->getIsUserDefined(),
                        'is_global' => $attribute->getIsGlobal(),
                        'is_required' => $attribute->getIsRequired(),
                        'is_unique' => $attribute->getIsUnique(),
                        'frontend_label' => $attribute->getFrontendLabel(),
                        'is_static' => $attribute->isStatic(),
                        'apply_to' => $attribute->getApplyTo(),
                        'type' => Import::getAttributeType($attribute),
                        'options' => isset($options['admin']) ? $options['admin'] : $options,
                        'options_store' => $options,
                        'additional_data' => \json_decode($attribute->getData('additional_data') ?? '', true),
                        'default_value' => $attribute->getDefaultValue() !== '' ? $attribute->getDefaultValue() : null,
                    ];
                }

                self::$attributeCodeToId[$attributeCode] = $attributeId;
                $this->_addAttributeParams(
                    $attributeSetName,
                    self::$commonAttributesCache[$attributeId],
                    $attribute
                );
            }
        }
    }

    /**
     * Prepare attributes values for save: exclude non-existent, static or with empty values attributes;
     * set default values if needed
     *
     * @param array $rowData
     * @param bool $withDefaultValue
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function prepareAttributesWithDefaultValueForSave(array $rowData, $withDefaultValue = true)
    {
        $resultAttrs = [];

        foreach ($this->_getProductAttributes($rowData) as $attrCode => $attrParams) {
            if ($attrParams['is_static']) {
                continue;
            }
            if (isset($rowData[$attrCode]) && trim($rowData[$attrCode]) !== '') {
                if (in_array($attrParams['type'], ['select', 'boolean'])) {
                    $attrOptions = $attrParams['options'];
                    $scopeStore = Product::SCOPE_STORE == $this->_entityModel->getRowScope($rowData);
                    $storeCode = isset($rowData['store_view_code']) ? $rowData['store_view_code'] : false;
                    if ($scopeStore && $storeCode && $attrParams['options_store'][$storeCode]) {
                        $attrOptions = $attrParams['options_store'][$storeCode];
                    }
                    if (isset($attrOptions[strtolower($rowData[$attrCode])])) {
                        $resultAttrs[$attrCode] = $attrOptions[strtolower($rowData[$attrCode])] ??
                            $rowData[$attrCode];
                    } else {
                        $resultAttrs[$attrCode] = $rowData[$attrCode];
                    }
                    if ($rowData[Product::COL_TYPE] == DownloadableProductType::TYPE_DOWNLOADABLE) {
                        $resultAttrs = array_merge($resultAttrs, $this->addAdditionalAttributes($rowData));
                    }
                } elseif ('multiselect' == $attrParams['type']) {
                    $resultAttrs[$attrCode] = [];
                    foreach ($this->_entityModel->parseMultiselectValues($rowData[$attrCode]) as $value) {
                        $resultAttrs[$attrCode][] = $attrParams['options'][strtolower($value)];
                    }
                    $resultAttrs[$attrCode] = implode(',', $resultAttrs[$attrCode]);
                } else {
                    $resultAttrs[$attrCode] = $rowData[$attrCode];
                }
            } elseif (array_key_exists($attrCode, $rowData)) {
                $resultAttrs[$attrCode] = $rowData[$attrCode];
            } elseif ($withDefaultValue && null !== $attrParams['default_value']) {
                $resultAttrs[$attrCode] = $attrParams['default_value'];
            }
        }

        return $resultAttrs;
    }
}
