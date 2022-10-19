<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Replace\Attribute\Catalog\Category;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Catalog\Api\Data\CategoryAttributeInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Catalog\Model\ResourceModel\Category\Attribute\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Category\Attribute\Collection;

/**
 * Class Options
 *
 * @package Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Replace\Attribute
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Options implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    private $attributeCollectionFactory;

    /**
     * @var array
     */
    private $options;

    /**
     * @param CollectionFactory $attributeCollectionFactory
     */
    public function __construct(
        CollectionFactory $attributeCollectionFactory
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
                $optionLabel = $this->makeLabel($attribute);
                if (!$optionLabel) {
                    continue;
                }
                /** @var CategoryAttributeInterface $attribute */
                $options[] = [
                    'value' => $attribute->getAttributeCode(),
                    'label' => $optionLabel,
                ];
            }
            $this->options = $options;
        }
        return $this->options;
    }

    /**
     * Get category attributes collection of only "text type" category attributes
     *
     * @return Collection
     */
    private function getAttributeCollection()
    {
        return $this->makeAttributeCollection()
            ->addFieldToFilter(CategoryAttributeInterface::BACKEND_TYPE, ['in' => ['varchar', 'text']])
            ->addFieldToFilter(CategoryAttributeInterface::FRONTEND_INPUT, ['in' => ['text', 'textarea']])
            ->setOrder(CategoryAttributeInterface::ATTRIBUTE_CODE, AbstractDb::SORT_ORDER_ASC);
    }

    /**
     * @param Attribute $attribute
     * @return string|bool
     */
    private function makeLabel(Attribute $attribute)
    {
        if (!$attribute->getDefaultFrontendLabel()) {
            return false;
        }
        return sprintf('%s (%s)', $attribute->getAttributeCode(), $attribute->getDefaultFrontendLabel());
    }

    /**
     * @return Collection
     */
    private function makeAttributeCollection()
    {
        return $this->attributeCollectionFactory->create();
    }
}
