<?php
/**
 * SupplierAttributeValue
 *
 * @copyright Copyright © 2019 Firebear Studio. All rights reserved.
 * @author    fbeardev@gmail.com
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\System;

use Magento\Eav\Model\Config;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class SupplierAttributeValue
 *
 * @package Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\System
 */
class SupplierAttributeValue implements OptionSourceInterface
{
    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * SupplierAttributeValue constructor.
     *
     * @param Config $eavConfig
     */
    public function __construct(
        Config $eavConfig
    ) {
        $this->eavConfig = $eavConfig;
    }

    /**
     * @param  string $attributeCode
     * @return array
     * @throws LocalizedException
     */
    public function toOptionArray(string $attributeCode = '')
    {
        $options = [];
        if ($attributeCode) {
            $attribute = $this->eavConfig->getAttribute('catalog_product', $attributeCode);
            $options[] = ['label' => __('Select Supplier'), 'value' => ''];
            if ($attribute->usesSource()) {
                foreach ($attribute->getSource()->getAllOptions() as $option) {
                    if (!empty($option['label'])) {
                        $options[] = [
                            'label' => $option['label'],
                            'value' => $option['value']
                        ];
                    }
                }
            }
        }
        return $options;
    }
}
