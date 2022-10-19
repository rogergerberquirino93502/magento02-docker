<?php

/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Form\Categories;

use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\App\Cache\Type\Block as TypeBlock;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use function is_object;

/**
 * Options tree for "Categories" field
 */
class Options extends \Magento\Catalog\Ui\Component\Product\Form\Categories\Options
{

    const CATEGORY_TREE_ID = 'CATALOG_PRODUCT_CATEGORY_TREE';

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    protected $cacheManager;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    protected $serializerInterface;

    /**
     * @var \Magento\Catalog\Model\CategoryRepository;
     */
    protected $categoryRepository;

    /**
     * @var \Psr\Log\LoggerInterface;
     */
    protected $loggerInterface;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface;
     */
    protected $storeManager;

    /**
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param RequestInterface $request
     * @param CategoryRepository $categoryRepository
     * @param CacheInterface $cacheManager
     * @param SerializerInterface $serializerInterface
     * @param LoggerInterface $loggerInterface
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CategoryCollectionFactory $categoryCollectionFactory,
        RequestInterface $request,
        CategoryRepository $categoryRepository,
        CacheInterface $cacheManager,
        SerializerInterface $serializerInterface,
        LoggerInterface $loggerInterface,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($categoryCollectionFactory, $request);
        $this->categoryRepository = $categoryRepository;
        $this->cacheManager = $cacheManager;
        $this->serializerInterface = $serializerInterface;
        $this->loggerInterface = $loggerInterface;
        $this->storeManager = $storeManager;
    }

    /**
     * Retrieve categories tree
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getCategoriesTree()
    {
        $storeId = $this->request->getParam('store');
        if (empty($storeId)) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        $filter = 'import_job_form_' . $storeId;
        $categoryTree = $this->cacheManager->load(self::CATEGORY_TREE_ID . '_' . $filter);
        if (!empty($categoryTree)) {
            $this->categoriesTree = $this->serializerInterface->unserialize($categoryTree);
        }

        if ($this->categoriesTree === null) {
            $this->categoriesTree = [];
            $matchingCollection = $this->categoryCollectionFactory->create();

            $matchingCollection
                ->addAttributeToSelect('path')
                ->addAttributeToFilter('entity_id', ['neq' => CategoryModel::TREE_ROOT_ID])
                ->setStoreId($storeId);

            /** @var \Magento\Catalog\Model\Category $category */
            foreach ($matchingCollection as $category) {
                $pathIds = $category->getPathIds();
                $path = [];
                foreach ($pathIds as $categoryId) {
                    if ($categoryId != 1 && !empty(trim($categoryId)) && !empty($storeId)) {
                        try {
                            $cat = $this->categoryRepository->get($categoryId, $storeId);
                        } catch (NoSuchEntityException $noSuchEntityException) {
                            //if exception found skip to avoid form break
                            $this->loggerInterface->critical($noSuchEntityException->getMessage());
                            continue;
                        }
                        if (is_object($cat)) {
                            $path[] = $cat->getName();
                        }
                    }
                }
                $categoryPath = implode('/', $path);

                $this->categoriesTree[] = [
                    'value' => $categoryPath,
                    'label' => $categoryPath
                ];
            }
        }

        $this->loggerInterface->debug($this->serializerInterface->serialize($this->categoriesTree));

        $this->cacheManager->save(
            $this->serializerInterface->serialize($this->categoriesTree),
            self::CATEGORY_TREE_ID . '_' . $filter,
            [CategoryModel::CACHE_TAG, TypeBlock::CACHE_TAG]
        );

        return $this->categoriesTree;
    }
}
