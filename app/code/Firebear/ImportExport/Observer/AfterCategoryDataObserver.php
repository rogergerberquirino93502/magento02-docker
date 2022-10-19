<?php
/**
 * AfterCategoryDataObserver
 *
 * @copyright Copyright © 2019 Firebear Studio. All rights reserved.
 * @author    fbeardev@gmail.com
 */

namespace Firebear\ImportExport\Observer;

use Firebear\ImportExport\Model\Import\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator;
use Magento\CatalogUrlRewrite\Model\CategoryUrlRewriteGenerator;
use Magento\CatalogUrlRewrite\Model\ObjectRegistryFactory;
use Magento\CatalogUrlRewrite\Service\V1\StoreViewService;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Model\Exception\UrlAlreadyExistsException;
use Magento\UrlRewrite\Model\MergeDataProvider;
use Magento\UrlRewrite\Model\MergeDataProviderFactory;
use Magento\UrlRewrite\Model\OptionProvider;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Magento\UrlRewrite\Service\V1\Data\UrlRewriteFactory;

/**
 * Class AfterCategoryDataObserver
 * @package Firebear\ImportExport\Observer
 */
class AfterCategoryDataObserver implements ObserverInterface
{
    /**
     * Url Key Attribute
     */
    const URL_KEY_ATTRIBUTE_CODE = 'url_key';

    /**
     * @var array
     */
    protected $vitalForGenerationFields = [
        'sku',
        'url_key',
        'url_path',
        'name',
        'url_key_create_redirect',
        'save_rewrites_history',
    ];

    /**
     * @var Category
     */
    private $import;

    /**
     * @var CategoryFactory
     */
    private $categoryFactory;

    /**
     * @var ObjectRegistryFactory
     */
    private $objectRegistryFactory;

    /**
     * @var CategoryUrlPathGenerator
     */
    private $categoryUrlPathGenerator;

    /**
     * @var StoreViewService
     */
    private $storeViewService;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var UrlPersistInterface
     */
    private $urlPersist;

    /**
     * @var UrlRewriteFactory
     */
    private $urlRewriteFactory;

    /**
     * @var UrlFinderInterface
     */
    private $urlFinder;

    /**
     * @var MergeDataProviderFactory|null
     */
    private $mergeDataProviderFactory;

    /**
     * @var CategoryCollectionFactory|null
     */
    private $categoryCollectionFactory;

    /**
     * @var MergeDataProvider
     */
    private $mergeDataProviderPrototype;

    /** @var array */
    private $categoryCache;

    /** @var array */
    private $categories = [];
    private $categoriesCache;
    /**
     * @var StoreInterface[]
     */
    private $storesList;

    /**
     * AfterCategoryDataObserver constructor.
     * @param CategoryFactory $categoryFactory
     * @param ObjectRegistryFactory $objectRegistryFactory
     * @param CategoryUrlPathGenerator $categoryUrlPathGenerator
     * @param StoreViewService $storeViewService
     * @param StoreManagerInterface $storeManager
     * @param UrlPersistInterface $urlPersist
     * @param UrlRewriteFactory $urlRewriteFactory
     * @param UrlFinderInterface $urlFinder
     * @param MergeDataProviderFactory|null $mergeDataProviderFactory
     * @param CategoryCollectionFactory|null $categoryCollectionFactory
     */
    public function __construct(
        CategoryFactory $categoryFactory,
        ObjectRegistryFactory $objectRegistryFactory,
        CategoryUrlPathGenerator $categoryUrlPathGenerator,
        StoreViewService $storeViewService,
        StoreManagerInterface $storeManager,
        UrlPersistInterface $urlPersist,
        UrlRewriteFactory $urlRewriteFactory,
        UrlFinderInterface $urlFinder,
        MergeDataProviderFactory $mergeDataProviderFactory = null,
        CategoryCollectionFactory $categoryCollectionFactory = null
    ) {
        $this->categoryFactory = $categoryFactory;
        $this->objectRegistryFactory = $objectRegistryFactory;
        $this->categoryUrlPathGenerator = $categoryUrlPathGenerator;
        $this->storeViewService = $storeViewService;
        $this->storeManager = $storeManager;
        $this->urlPersist = $urlPersist;
        $this->urlRewriteFactory = $urlRewriteFactory;
        $this->urlFinder = $urlFinder;
        if (!isset($mergeDataProviderFactory)) {
            $mergeDataProviderFactory = ObjectManager::getInstance()->get(MergeDataProviderFactory::class);
        }
        foreach ($storeManager->getStores(true) as $store) {
            $this->storesList[$store->getCode()] = $store->getId();
        }
        $this->mergeDataProviderPrototype = $mergeDataProviderFactory->create();
        $this->categoryCollectionFactory = $categoryCollectionFactory ?:
            ObjectManager::getInstance()->get(CategoryCollectionFactory::class);
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     * @throws UrlAlreadyExistsException
     */
    public function execute(Observer $observer)
    {
        $this->import = $observer->getEvent()->getAdapter();
        if ($categories = $observer->getEvent()->getBunch()) {
            foreach ($categories as $category) {
                $this->_populateForUrlGeneration($category);
            }
            $categoryUrls = $this->generateUrls();
            if ($categoryUrls) {
                $this->urlPersist->replace($categoryUrls);
            }
        }
    }

    /**
     * @param array $rowData
     * @return $this|null
     */
    protected function _populateForUrlGeneration(array $rowData)
    {
        if (empty($rowData[self::URL_KEY_ATTRIBUTE_CODE])) {
            return null;
        }
        $categoryName = str_replace(
            $this->import->getParameters()['category_levels_separator'],
            Category::DELIMITER_CATEGORY,
            $rowData[Category::COL_NAME]
        );
        $categoryId = $rowData['entity_id'] = $this->getCategoryId($categoryName);
        $category = $this->categoryFactory->create();
        $category->setId($rowData['entity_id']);

        foreach ($this->vitalForGenerationFields as $field) {
            if (isset($rowData[$field])) {
                $category->setData($field, $rowData[$field]);
            }
        }

        $storeId = $this->storeManager->getDefaultStoreView()->getId();
        $this->categories[$categoryId][$storeId] = $category;
        if (isset($rowData[Category::COL_STORE]) && !empty($rowData[Category::COL_STORE])) {
            if (isset($this->storesList[$rowData[Category::COL_STORE]])) {
                $storeId = $this->storesList[$rowData[Category::COL_STORE]];
            } else {
                error_log($rowData[Category::COL_STORE]. " could not find in storelist");
            }
        }

        $this->categories[$categoryId][$storeId] = $category;

        return $this;
    }

    /**
     * @param string $categoryName
     * @return int
     */
    private function getCategoryId(string $categoryName)
    {
        $categories = array_reverse($this->import->getInitialCategories());
        if (isset($categories[$categoryName])) {
            return $categories[$categoryName];
        }
        return null;
    }

    /**
     * Generate product url rewrites
     *
     * @return UrlRewrite[]
     * @throws LocalizedException
     */
    protected function generateUrls()
    {
        $mergeDataProvider = clone $this->mergeDataProviderPrototype;
        $mergeDataProvider->merge($this->canonicalUrlRewriteGenerate());
        $mergeDataProvider->merge($this->categoriesUrlRewriteGenerate());
        $mergeDataProvider->merge($this->currentUrlRewritesRegenerate());

        unset($this->categories);
        $this->categories = [];

        return $mergeDataProvider->getData();
    }

    /**
     * Generate list based on store view
     *
     * @return UrlRewrite[]
     * @throws NoSuchEntityException
     */
    protected function canonicalUrlRewriteGenerate()
    {
        $urls = [];
        foreach ($this->categories as $categoryId => $categoriesByStore) {
            foreach ($categoriesByStore as $storeId => $category) {
                if ($this->categoryUrlPathGenerator->getUrlPath($category)) {
                    $urls[] = $this->urlRewriteFactory->create()
                        ->setEntityType(CategoryUrlRewriteGenerator::ENTITY_TYPE)
                        ->setEntityId($categoryId)
                        ->setRequestPath($this->categoryUrlPathGenerator->getUrlPathWithSuffix($category, $storeId))
                        ->setTargetPath($this->categoryUrlPathGenerator->getCanonicalUrlPath($category))
                        ->setStoreId($storeId);
                }
            }
        }

        return $urls;
    }

    /**
     * Generate list based on categories.
     *
     * @return UrlRewrite[]
     * @throws LocalizedException
     */
    protected function categoriesUrlRewriteGenerate()
    {
        $urls = [];
        foreach ($this->categories as $categoryId => $categoriesByStore) {
            foreach ($categoriesByStore as $storeId => $categoryStore) {
                $category = $this->getCategoryById($categoryStore->getId(), $storeId);
                if ($category->getParentId() == \Magento\Catalog\Model\Category::TREE_ROOT_ID
                    || $category->getId() == \Magento\Catalog\Model\Category::TREE_ROOT_ID
                ) {
                    continue;
                }
                if (!empty($categoryStore->getUrlPath())) {
                    $category->setUrlPath($categoryStore->getUrlPath());
                }
                $requestPath = $this->categoryUrlPathGenerator->getUrlPathWithSuffix($category, $storeId);
                $urls[] = $this->urlRewriteFactory->create()
                    ->setEntityType(CategoryUrlRewriteGenerator::ENTITY_TYPE)
                    ->setEntityId($categoryId)
                    ->setRequestPath($requestPath)
                    ->setTargetPath($this->categoryUrlPathGenerator->getCanonicalUrlPath($category))
                    ->setStoreId($storeId)
                    ->setMetadata(['category_id' => $category->getId()]);
            }
        }
        return $urls;
    }

    /**
     * Get category by id considering store scope.
     *
     * @param int $categoryId
     * @param int $storeId
     * @return \Magento\Catalog\Model\Category|DataObject
     * @throws LocalizedException
     */
    private function getCategoryById($categoryId, $storeId)
    {
        if (!isset($this->categoriesCache[$categoryId][$storeId])) {
            /** @var CategoryCollection $categoryCollection */
            $categoryCollection = $this->categoryCollectionFactory->create();
            $categoryCollection->addIdFilter([$categoryId])
                ->setStoreId($storeId)
                ->addAttributeToSelect('name')
                ->addAttributeToSelect('url_key')
                ->addAttributeToSelect('url_path');
            $this->categoriesCache[$categoryId][$storeId] = $categoryCollection->getFirstItem();
        }

        return $this->categoriesCache[$categoryId][$storeId];
    }

    /**
     * Generate list based on current rewrites
     *
     * @return UrlRewrite[]
     */
    protected function currentUrlRewritesRegenerate()
    {
        $currentUrlRewrites = $this->urlFinder->findAllByData(
            [
                UrlRewrite::STORE_ID => array_keys($this->storesList),
                UrlRewrite::ENTITY_ID => array_keys($this->categories),
                UrlRewrite::ENTITY_TYPE => CategoryUrlRewriteGenerator::ENTITY_TYPE,
            ]
        );

        $urlRewrites = [];
        foreach ($currentUrlRewrites as $currentUrlRewrite) {
            $category = $this->retrieveCategoryFromMetadata($currentUrlRewrite);
            if ($category === false) {
                continue;
            }
            $url = $currentUrlRewrite->getIsAutogenerated()
                ? $this->generateForAutogenerated($currentUrlRewrite)
                : $this->generateForCustom($currentUrlRewrite);
            $urlRewrites = array_merge($urlRewrites, $url);
        }

        $this->categories = null;
        return $urlRewrites;
    }

    /**
     * Retrieve category from url metadata.
     *
     * @param UrlRewrite $url
     * @return \Magento\Catalog\Model\Category|null|bool
     */
    protected function retrieveCategoryFromMetadata($url)
    {
        $metadata = $url->getMetadata();
        if (isset($metadata['category_id'])) {
            $category = $this->import->getCategoryById($metadata['category_id']);
            return $category === null ? false : $category;
        }
        return null;
    }

    /**
     * Generate url-rewrite for outogenerated url-rewirte.
     *
     * @param UrlRewrite $url
     * @return array
     */
    protected function generateForAutogenerated(UrlRewrite $url)
    {
        $storeId = $url->getStoreId();
        $categoryId = $url->getEntityId();
        if (isset($this->categories[$categoryId][$storeId])) {
            $category = $this->categories[$categoryId][$storeId];
            if (!$category->getData('save_rewrites_history')) {
                return [];
            }
            $targetPath = $this->categoryUrlPathGenerator->getUrlPathWithSuffix($category, $storeId);
            if ($url->getRequestPath() === $targetPath) {
                return [];
            }
            return [
                $this->urlRewriteFactory->create()
                    ->setEntityType(CategoryUrlRewriteGenerator::ENTITY_TYPE)
                    ->setEntityId($categoryId)
                    ->setRequestPath($url->getRequestPath())
                    ->setTargetPath($targetPath)
                    ->setRedirectType(OptionProvider::PERMANENT)
                    ->setStoreId($storeId)
                    ->setDescription($url->getDescription())
                    ->setIsAutogenerated(0)
                    ->setMetadata($url->getMetadata())
            ];
        }
        return [];
    }

    /**
     * @param UrlRewrite $url
     * @return array
     */
    protected function generateForCustom(UrlRewrite $url)
    {
        $storeId = $url->getStoreId();
        $categoryId = $url->getEntityId();
        if (isset($this->categories[$categoryId][$storeId])) {
            $category = $this->categories[$categoryId][$storeId];
            $targetPath = $url->getRedirectType()
                ? $this->categoryUrlPathGenerator->getUrlPathWithSuffix($category, $storeId)
                : $url->getTargetPath();
            if ($url->getRequestPath() === $targetPath) {
                return [];
            }
            return [
                $this->urlRewriteFactory->create()
                    ->setEntityType(CategoryUrlRewriteGenerator::ENTITY_TYPE)
                    ->setEntityId($categoryId)
                    ->setRequestPath($url->getRequestPath())
                    ->setTargetPath($targetPath)
                    ->setRedirectType($url->getRedirectType())
                    ->setStoreId($storeId)
                    ->setDescription($url->getDescription())
                    ->setIsAutogenerated(0)
                    ->setMetadata($url->getMetadata())
            ];
        }
        return [];
    }
}
