<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Replace\Attribute\Customer\Address;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Customer\Model\ResourceModel\Address\Attribute\CollectionFactory;
use Magento\Eav\Model\Entity\Attribute;

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
                $options[] = [
                    'value' => $attribute->getAttributeCode(),
                    'label' => $this->makeLabel($attribute),
                ];
            }
            $this->options = $options;
        }
        return $this->options;
    }

    /**
     * Get customer address attributes collection of only "text type"  attributes
     *
     * @return \Magento\Customer\Model\ResourceModel\Address\Attribute\Collection
     */
    private function getAttributeCollection()
    {
        return $this->makeAttributeCollection()
            ->addVisibleFilter()
            ->addFieldToFilter(Attribute::FRONTEND_INPUT, ['in' => ['text', 'textarea']])
            ->setOrder(Attribute::ATTRIBUTE_CODE, AbstractDb::SORT_ORDER_ASC);
    }

    /**
     * @param Attribute $attribute
     * @return string
     */
    private function makeLabel(Attribute $attribute)
    {
        return sprintf('%s (%s)', $attribute->getAttributeCode(), $attribute->getDefaultFrontendLabel());
    }

    /**
     * @return \Magento\Customer\Model\ResourceModel\Address\Attribute\Collection
     */
    private function makeAttributeCollection()
    {
        return $this->attributeCollectionFactory->create();
    }
}
