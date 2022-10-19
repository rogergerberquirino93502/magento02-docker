<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Product;

use Firebear\ImportExport\Model\Import\Product;
use Magento\Framework\Stdlib\DateTime;

/**
 * Class CategoryProcessor
 *
 * @package Firebear\ImportExport\Model\Import\Product
 */
class CategoryProcessor extends \Magento\CatalogImportExport\Model\Import\Product\CategoryProcessor
{
    /**
     * Delimiter in category path.
     */
    const DELIMITER_CATEGORY = '/';

    protected $generateUrl;

    protected $resource;

    protected $storeId;

    protected $attributes = [
        'name',
        'is_active',
        'include_in_menu',
        'url_key',
        'url_path'
    ];

    private $categoryProductPosition = [];

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $localeDate;

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryColFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
    ) {
        $this->localeDate = $localeDate;
        parent::__construct($categoryColFactory, $categoryFactory);
    }

    /**
     * @return $this|\Magento\CatalogImportExport\Model\Import\Product\CategoryProcessor
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function initCategories()
    {
        if (empty($this->categories)) {
            $collection = $this->categoryColFactory->create();
            $collection->addAttributeToSelect('name')
                ->addAttributeToSelect('url_key')
                ->addAttributeToSelect('url_path');
            $collection->setStoreId(\Magento\Store\Model\Store::DEFAULT_STORE_ID);
            /* @var $collection \Magento\Catalog\Model\ResourceModel\Category\Collection */
            foreach ($collection as $category) {
                $category->setName(str_replace('/', '&#47;', $category->getName()));
                $structure = explode(self::DELIMITER_CATEGORY, $category->getPath());
                $pathSize = count($structure);

                $this->categoriesCache[$category->getId()] = $category;
                if ($pathSize > 1) {
                    $path = [];
                    for ($i = 1; $i < $pathSize; $i++) {
                        $cat = $collection->getItemById((int)$structure[$i]);
                        if (\is_object($cat)) {
                            $name = $cat->getName();
                            $path[] = $this->quoteDelimiter($name);
                        }
                    }
                    /** @var string $index */
                    $index = $this->standardizeString(
                        implode(self::DELIMITER_CATEGORY, $path)
                    );
                    $this->categories[$index] = $category->getId();
                }
            }
        }
        return $this;
    }

    /**
     * Quoting delimiter character in string.
     *
     * @param string $string
     * @return string
     */
    private function quoteDelimiter($string)
    {
        return str_replace(self::DELIMITER_CATEGORY, '\\' . self::DELIMITER_CATEGORY, $string);
    }

    /**
     * Standardize a string.
     * For now it performs only a lowercase action, this method is here to include more complex checks in the future
     * if needed.
     *
     * @param string $string
     * @return string
     */
    private function standardizeString($string)
    {
        return mb_strtolower($string);
    }

    /**
     * @param $rowData
     * @param $separator
     * @return array
     */
    public function getRowCategories($rowData, $separator)
    {
        $catData = $catPosData = $explodeCatPosData = [];
        $categoryIds = [];
        $colCategoryNotEmpty = !empty($rowData[Product::COL_CATEGORY]);
        if ($colCategoryNotEmpty) {
            $catData = explode($separator, $rowData[Product::COL_CATEGORY]);
        }
        if (!empty($rowData[Product::COL_CATEGORY . '_position'])) {
            $catPosDataValue = explode($separator, $rowData[Product::COL_CATEGORY . '_position']);
            foreach ($catPosDataValue as $value) {
                if (empty($value)) {
                    continue;
                }
                $catPos = explode(Product::PAIR_NAME_VALUE_SEPARATOR, $value);
                $catPosData[] = $catPos[0];
                if (isset($catPos[1])) {
                    $explodeCatPosData[$catPos[0]] = $catPos[1];
                }
            }
        }

        $this->setRowCategoryPosition();
        $catData = (!empty($catData)) ? $catData : $catPosData;
        foreach ($catData as $cData) {
            try {
                if ($cData == '/' || $cData == '') {
                    continue;
                }
                $secondCategory = null;
                if (is_numeric($cData)) {
                    $collectionId = $this->categoryColFactory->create()->addFieldToFilter('entity_id', $cData);
                    if ($collectionId->getSize()) {
                        $secondCategory = $collectionId->getFirstItem()->getId();
                    }
                }

                if (empty($secondCategory)) {
                    if ($colCategoryNotEmpty) {
                        $secondCategory = $this->upsertCategory($cData);
                    } else {
                        /** @var string $index */
                        $index = mb_strtolower($cData);
                        $secondCategory = $this->categories[$index] ?? null;
                    }
                }

                if (!empty($secondCategory) && isset($explodeCatPosData[$cData])) {
                    $this->categoryProductPosition[$secondCategory] = $explodeCatPosData[$cData];
                }

                if ($colCategoryNotEmpty) {
                    $categoryIds[] = $secondCategory;
                }
            } catch (\Firebear\ImportExport\Exception\NotValidCategoryException $e) {
                continue;
            }
        }

        return $categoryIds;
    }

    /**
     * @return array
     */
    public function getRowCategoryPosition()
    {
        return $this->categoryProductPosition;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRowCategoryPosition($value = [])
    {
        $this->categoryProductPosition = $value;
        return $this;
    }

    /**
     * @param $categoryName
     * @param int $parentId
     * @return int
     * @throws \Exception
     */
    protected function createCategory($categoryName, $parentId)
    {
        $categoryName = str_replace('&#47;', '/', $categoryName);
        /** @var \Magento\Catalog\Model\Category $category */
        $category = $this->categoryFactory->create();
        if (!($parentCategory = $this->getCategoryById($parentId))) {
            $parentCategory = $this->categoryFactory->create()->load($parentId);
        }
        $category->setPath($parentCategory->getPath());
        $category->setParentId($parentId);
        $category->setName($this->unquoteDelimiter($categoryName));
        $category->setIsActive(true);
        $category->setIncludeInMenu(true);
        $category->setAttributeSetId($category->getDefaultAttributeSetId());
        $parentCategoryUrlPath = $parentCategory->getUrlPath() ?? '';
        $urlKey = $category->formatUrlKey($categoryName);
        if (!empty($parentCategoryUrlPath)) {
            $urlPath = $parentCategory->getUrlPath() . self::DELIMITER_CATEGORY . $urlKey;
        } else {
            $urlPath = $category->formatUrlKey($categoryName);
        }
        $urlKey = $this->checkUrlKeyDuplicates(
            $urlKey,
            $category,
            0,
            $urlPath
        );
        $category->setUrlKey($urlKey);
        try {
            $category->save();
            $this->categoriesCache[$category->getId()] = $category;
        } catch (\Exception $e) {
            $this->addFailedCategory($category->getName(), $e);
            throw new \Firebear\ImportExport\Exception\NotValidCategoryException(__($e->getMessage()), $e);
        }

        return $category->getId();
    }

    /**
     * @param string $category
     * @param \Magento\Framework\Exception\AlreadyExistsException $exception
     * @return $this
     */
    private function addFailedCategory($category, $exception)
    {
        $this->failedCategories[] =
            [
                'category' => $category,
                'exception' => $exception,
            ];
        return $this;
    }

    protected function checkUrlKeyDuplicates($urlKey, $category, $number, $urlPath = '')
    {
        if ($this->getGenerateUrl()) {
            $urlPath = $urlPath ?? $urlKey;
            $resource = $this->getResource();
            $select = $resource->getConnection()->select()->from(
                ['url_rewrite' => $resource->getTable('url_rewrite')],
                ['request_path', 'store_id']
            )->where('request_path LIKE "%' . $urlPath . '"')
                ->orWhere('request_path LIKE "%' . $urlPath . '.html"');
            $urlKeyDuplicates = $resource->getConnection()->fetchAssoc(
                $select
            );
            if (count($urlKeyDuplicates) > 0) {
                $urlKey = $this->checkUrlKeyDuplicates(
                    $category->formatUrlKey($category->getName()) . '-' . $number,
                    $category,
                    $number + 1
                );
            }
        }

        return $urlKey;
    }

    public function setGeneratUrl($number)
    {
        $this->generateUrl = $number;
    }

    public function getGenerateUrl()
    {
        return $this->generateUrl;
    }

    public function setResource($resource)
    {
        $this->resource = $resource;
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
    }

    public function getStoreId()
    {
        return $this->storeId;
    }

    public function saveCategoryEntity(array $entityRowsIn)
    {
        static $entityTable = null;
        $category = $this->categoryFactory->create();
        $resource = $category->getResource();

        if (!$entityTable) {
            $entityTable = $resource->getTable('catalog_category_entity');
        }
        $connection = $resource->getConnection();
        if ($entityRowsIn) {
            $name = $entityRowsIn['name'];
            unset($entityRowsIn['name']);
            $connection->insertMultiple($entityTable, $entityRowsIn);
            $id = $connection->lastInsertId($entityTable);
            $entityRowsUp = [
                'path' => $entityRowsIn['path'] . "/" . $id,
                'updated_at' => $this->localeDate->date()->format(DateTime::DATETIME_PHP_FORMAT),
                'entity_id' => $id
            ];
            $connection->insertOnDuplicate($entityTable, $entityRowsUp, ['updated_at', 'path']);
            $parentCategory = $this->categoryFactory->create()->load($entityRowsIn['parent_id']);
            $parentCategoryUrlPath = $parentCategory->getUrlPath() ?? '';
            $urlKey = $category->formatUrlKey($name);
            if (!empty($parentCategoryUrlPath)) {
                $urlPath = $parentCategory->getUrlPath() . self::DELIMITER_CATEGORY . $urlKey;
            } else {
                $urlPath = $category->formatUrlKey($name);
            }
            $urlKey = $this->checkUrlKeyDuplicates(
                $urlKey,
                $category,
                0,
                $urlPath
            );
            $attributes = [];
            foreach ($this->attributes as $attr) {
                $attribute = $resource->getAttribute($attr);
                $attrId = $attribute->getId();
                $attrTable = $attribute->getBackend()->getTable();
                $storeIds = [0];
                if (!$this->getStoreId()) {
                    $storeIds[] = $this->getStoreId();
                }
                $attrValue = null;
                if ($attr == 'name') {
                    $attrValue = $name;
                }
                if (in_array($attr, ['is_active', 'include_in_menu'])) {
                    $attrValue = true;
                }
                if ($attr == 'url_key') {
                    $attrValue = $urlKey;
                }
                if ($attr == 'url_path') {
                    $attrValue = $urlPath ?? $urlKey;
                }

                foreach ($storeIds as $storeId) {
                    if (!empty($attrValue)) {
                        $attributes[$attrTable][$id][$attrId][$storeId] = $attrValue;
                    }
                }
                if (!empty($attributes)) {
                    $this->saveCategoryAttributes(
                        $attributes
                    );
                }
            }

            return $id;
        }

        return null;
    }

    protected function saveCategoryAttributes(array $attributesData)
    {
        $category = $this->categoryFactory->create();
        $resource = $category->getResource();
        $connection = $resource->getConnection();
        $metadataPool = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\EntityManager\MetadataPool::class);
        $linkFieldId = $metadataPool
            ->getMetadata(\Magento\Catalog\Api\Data\CategoryInterface::class)
            ->getLinkField();
        foreach ($attributesData as $tableName => $idData) {
            $tableData = [];
            foreach ($idData as $id => $attributes) {
                foreach ($attributes as $attributeId => $storeValues) {
                    foreach ($storeValues as $storeId => $storeValue) {
                        $tableData[] = [
                            $linkFieldId => $id,
                            'attribute_id' => $attributeId,
                            'store_id' => $storeId,
                            'value' => $storeValue,
                        ];
                    }
                }
            }
            $connection->insertOnDuplicate($tableName, $tableData, ['value']);
        }

        return $this;
    }

    /**
     * Remove quoting delimiter in string.
     *
     * @param string $string
     * @return string
     */
    private function unquoteDelimiter($string)
    {
        return str_replace('\\' . self::DELIMITER_CATEGORY, self::DELIMITER_CATEGORY, $string);
    }
}
