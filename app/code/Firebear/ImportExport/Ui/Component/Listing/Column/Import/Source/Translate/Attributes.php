<?php
/**
 * Attributes
 *
 * @copyright Copyright © 2019 Firebear Studio. All rights reserved.
 * @author    Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Translate;

use Firebear\ImportExport\Ui\Component\Listing\Column\Entity\Import\Attributes\SystemOptions;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttribute;
use Magento\Framework\Data\Collection\AbstractDb;
use function in_array;

/**
 * Class Attributes
 *
 * @package Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Translate
 */
class Attributes extends SystemOptions
{
    private $excludeBackendType = [
        'decimal',
        'datetime',
        'int',
    ];

    private $excludeFrontendType = [
        'gallery',
        'date',
        'select',
        'media_image',
    ];

    /**
     * @param int $withoutGroup
     *
     * @return array
     */
    public function toOptionArray($withoutGroup = 0)
    {
        $attributeCollection = $this->getAttributeCollection()
            ->addFieldToFilter('attribute_code', ['nin' => $this->getExcludedAttributes()]);

        $subOptions = [];
        /**
         * @var EavAttribute $attribute
        */
        foreach ($attributeCollection as $attribute) {
            if ($attribute->getBackendModel()
                || in_array($attribute->getBackendType(), $this->excludeBackendType, true)
                || in_array($attribute->getFrontendInput(), $this->excludeFrontendType, true)
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

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    public function getAttributeCollection()
    {
        $this->attributeCollection = $this->attributeFactory
            ->create()
            ->setOrder('attribute_code', AbstractDb::SORT_ORDER_ASC);

        return $this->attributeCollection;
    }

    /**
     * @return array
     */
    public function getExcludedAttributes()
    {
        $attributes = [
            'sku',
            'url_path',
            'url_key',
            'links_title',
            'samples_title',
            'required_options',
            'has_options'
        ];
        return $attributes;
    }
}
