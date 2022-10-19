<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Context;
use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;

/**
 * Class CategoryNew
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Job
 */
class CategoryNew extends JobController
{
    /**
     * @var CategoryCollectionFactory
     */
    private $collectionCategoryFactory;

    /**
     * CategoryNew constructor.
     *
     * @param Context $context
     * @param CategoryCollectionFactory $collectionCategoryFactory
     */
    public function __construct(
        Context $context,
        CategoryCollectionFactory $collectionCategoryFactory
    ) {
        parent::__construct($context);
        $this->collectionCategoryFactory = $collectionCategoryFactory;
    }

    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create($this->resultFactory::TYPE_JSON);

        if ($this->getRequest()->isAjax()) {
            $newCategoryId = $this->getRequest()->getParam('categoryId');
            $categoryPath = $this->getCategoryPathById($newCategoryId);
            return $resultJson->setData(
                [
                    'data' => $categoryPath
                ]
            );
        }
    }

    /**
     * @param int $categoryId
     * @return string
     */
    private function getCategoryPathById($categoryId)
    {
        $storeId = $this->getRequest()->getParam('store');

        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $collection */
        $collection = $this->collectionCategoryFactory->create();

        $collection->addAttributeToFilter('entity_id', $categoryId)
            ->addAttributeToSelect(['name', 'is_active', 'parent_id', 'path'])
            ->setStoreId($storeId);
        $categoryFullPath = '';
        foreach ($collection as $category) {
            $categoryPath = $category->getPath();
            $explodeCategoryPath = explode('/', $categoryPath);
            foreach ($explodeCategoryPath as $categoryId) {
                if ($categoryId == 1) {
                    continue;
                }
                $collectionPathCategory = $this->collectionCategoryFactory->create();
                $collectionPathCategory->addAttributeToFilter('entity_id', $categoryId)
                    ->addAttributeToSelect(['name', 'is_active', 'parent_id', 'path'])
                    ->setStoreId($storeId);
                foreach ($collectionPathCategory as $categoryPath) {
                    if (empty($categoryFullPath)) {
                        $categoryFullPath = $categoryPath->getName();
                    } else {
                        $categoryFullPath .= '/' . $categoryPath->getName();
                    }
                }
            }
        }

        return $categoryFullPath;
    }
}
