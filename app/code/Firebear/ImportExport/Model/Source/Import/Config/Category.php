<?php
/**
 * Category
 *
 * @copyright Copyright © 2018 Firebear Studio. All rights reserved.
 * @author    Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Import\Config;

use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;

/**
 * Config category source
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Category implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Category collection factory
     *
     * @var CollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * Category constructor.
     *
     * @param CollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        CollectionFactory $categoryCollectionFactory
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function toOptionArray()
    {
        /**
         * @var  $collection
         */
        $collection = $this->categoryCollectionFactory->create();

        $collection->addAttributeToSelect('name')->addRootLevelFilter()->load();

        $options = [];

        $options[] = [
            'value' => 0,
            'label' => __('--Please Select--')
        ];

        foreach ($collection as $category) {
            $childCategories = $this->categoryCollectionFactory->create();
            $childCategories->addAttributeToSelect('name')
                ->addFieldToFilter('path', ['neq' => '1'])
                ->addFieldToFilter('level', ['eq' => 2])
                ->addFieldToFilter('parent_id', ['eq' => $category->getId()])
                ->load();
            $childCategoriesArray = [];
            $childCategoriesArray[] = [
                'value' => $category->getId(),
                'label' => __('Root Category %1', $category->getName()),
            ];
            foreach ($childCategories as $childCategory) {
                $childCategoriesArray[] = [
                    'label' => $childCategory->getName(),
                    'value' => $childCategory->getId(),
                ];
            }

            $options[] = [
                'value' => $category->getId(),
                'label' => $category->getName(),
                'optgroup' => $childCategoriesArray,
            ];
        }

        return $options;
    }
}
