<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Replace\Attribute\Catalog\Product;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Framework\Data\Collection\AbstractDb;

/**
 * Class Options
 * @package Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Replace\Attribute
 */
class Options implements OptionSourceInterface
{
    /** @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory */
    private $attributeCollectionFactory;

    /** @var array */
    private $options;

    /**
     * Map between import file fields and system fields/attributes
     *
     * @var mixed[]
     */
    private $fieldsMap = [
        'meta_keyword' => 'meta_keywords',
    ];

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeCollectionFactory
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeCollectionFactory
    ) {
        $this->attributeCollectionFactory = $attributeCollectionFactory;
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        if ($this->options === null) {
            $options = [];
            foreach ($this->getAttributeCollection() as $attribute) {
                $value = $attribute->getAttributeCode();
                $value = isset($this->fieldsMap[$value]) ? $this->fieldsMap[$value] : $value;
                /** @var ProductAttributeInterface $attribute */
                $options[] = [
                    'value' => $value,
                    'label' => $this->makeLabel($attribute),
                ];
            }
            $this->options = $options;
        }
        return $this->options;
    }

    /**
     * Get product attributes collection of only "text type" product attributes
     * As we need product attributes for replacing values so we need "text type product attributes"
     * We need to filter not only by backend_type, but also by frontend_input
     * I.e. `image` attribute has "backend_type" => "varchar", but "frontend_input" => "media_image"
     * Or `gift_message_available` attribute has "backend_type" => "varchar", but "frontend_input" => "select"
     * We need 'backend_type' in ['varchar', 'text'] AND 'frontend_input' in ['text', 'textarea']
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    private function getAttributeCollection()
    {
        return $this->makeAttributeCollection()
            ->addVisibleFilter()
            ->addFieldToFilter(ProductAttributeInterface::BACKEND_TYPE, ['in' => ['varchar', 'text']])
            ->addFieldToFilter(ProductAttributeInterface::FRONTEND_INPUT, ['in' => ['text', 'textarea']])
            ->setOrder(ProductAttributeInterface::ATTRIBUTE_CODE, AbstractDb::SORT_ORDER_ASC);
    }

    /**
     * @param ProductAttributeInterface $attribute
     * @return string
     */
    private function makeLabel(ProductAttributeInterface $attribute)
    {
        return sprintf('%s (%s)', $attribute->getAttributeCode(), $attribute->getDefaultFrontendLabel());
    }

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    private function makeAttributeCollection()
    {
        return $this->attributeCollectionFactory->create();
    }
}
