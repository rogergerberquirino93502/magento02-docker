<?php
/**
 * SupplierAttribute
 *
 * @copyright Copyright © 2019 Firebear Studio. All rights reserved.
 * @author    fbeardev@gmail.com
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\System;

use Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Translate\Attributes;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttribute;

/**
 * Class SupplierAttribute
 *
 * @package Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\System
 */
class SupplierAttribute extends Attributes
{
    /** @var array  */
    protected $excludeBackendType = [
        'decimal',
        'datetime',
    ];

    /**
     * @param int $withoutGroup
     * @return array
     */
    public function toOptionArray($withoutGroup = 0)
    {
        $attributeCollection = $this->getAttributeCollection()
            ->addFieldToFilter('frontend_input', ['eq' => 'select']);

        $subOptions = [];
        $subOptions[] = ['label' => __('Select Attribute Code'), 'value' => ''];
        /**
         * @var EavAttribute $attribute
         */
        foreach ($attributeCollection as $attribute) {
            if ($attribute->getBackendModel()
                || in_array($attribute->getBackendType(), $this->excludeBackendType, true)
            ) {
                continue;
            }
            $label = (!$withoutGroup) ?
                $attribute->getAttributeCode() . ' (' . $attribute->getFrontendLabel() . ')' :
                $attribute->getAttributeCode();
            $subOptions[] =
                [
                    'label' => $label,
                    'value' => $attribute->getAttributeCode(),
                ];
        }
        return $subOptions;
    }
}
