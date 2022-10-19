<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Import\UrlRewrite\EntityHandler;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Firebear\ImportExport\Model\Import\UrlRewrite\AbstractEntityHandler;
use Firebear\ImportExport\Model\Import\UrlRewrite\EntityHandlerInterface;

/**
 * Category handler
 */
class Category extends AbstractEntityHandler implements EntityHandlerInterface
{
    /** @var string  */
    const ENTITY_TYPE = 'category';

    /**
     * Entity Id column name
     */
    const COLUMN_ENTITY_ID = 'entity_id';

    /**
     * Entity type column name
     */
    const COLUMN_ENTITY_TYPE = 'entity_type';

    /**
     * Category name column name
     */
    const COLUMN_CATEGORY_NAME = 'category';

    /**
     * Store id column name
     */
    const COLUMN_STORE_ID = 'store_id';

    /**
     * Url rewrite_id column name
     */
    const COLUMN_URL_REWRITE_ID = 'url_rewrite_id';

    /**
     * Target path column name
     */
    const COLUMN_TARGET_PATH = 'target_path';

    /**
     * Category collection
     *
     * @var \Magento\Catalog\Model\ResourceModel\Category\Collection
     */
    protected $_collection;

    /**
     * Category collection factory
     *
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * Error codes
     */
    const ERROR_CATEGORY_NAME_NOT_FOUND = 'urlRewriteSkuNotFound';
    const ERROR_URL_REWRITE_ID_IS_EMPTY = 'urlRewriteIdIsEmpty';
    const ERROR_STORE_ID_IS_EMPTY = 'storeIdIsEmpty';

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_messageTemplates = [
        self::ERROR_CATEGORY_NAME_NOT_FOUND => 'Category with specified name not found',
        self::ERROR_URL_REWRITE_ID_IS_EMPTY => 'Url rewrite id is empty',
        self::ERROR_STORE_ID_IS_EMPTY => 'Store Id is empty',
    ];

    /**
     * Initialize handler
     *
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->_collectionFactory = $collectionFactory;
    }

    /**
     * Validate row data for replace behaviour
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return void
     */
    public function validateRowForReplace(array $rowData, $rowNumber)
    {
        $this->validateRowForUpdate($rowData, $rowNumber);
    }

    /**
     * Validate row data for delete behaviour
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return void
     */
    public function validateRowForDelete(array $rowData, $rowNumber)
    {
        $this->validateRowForUpdate($rowData, $rowNumber);
    }

    /**
     * Validate row data for update behaviour
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return void
     */
    public function validateRowForUpdate(array $rowData, $rowNumber)
    {
        if (!empty($rowData[self::COLUMN_CATEGORY_NAME])) {
            if (empty($rowData[self::COLUMN_STORE_ID])) {
                $this->addRowError(self::ERROR_STORE_ID_IS_EMPTY, $rowNumber);
            } else {
                $entityId = $this->_getCategory($rowData);
                if (!$entityId) {
                    $this->addRowError(self::ERROR_CATEGORY_NAME_NOT_FOUND, $rowNumber);
                }
            }
        }
    }

    /**
     * Prepare row data for update behaviour
     *
     * @param array $rowData
     * @return array
     */
    public function prepareRowForUpdate(array $rowData)
    {
        if (!empty($rowData[self::COLUMN_CATEGORY_NAME])) {
            $entityId = $this->_getCategory($rowData);
            if ($entityId) {
                $rowData[self::COLUMN_ENTITY_ID] = $entityId;
            }
            if (empty($rowData[self::COLUMN_ENTITY_TYPE])) {
                $rowData[self::COLUMN_ENTITY_TYPE] = 'category';
            }
        }

        if (!empty($rowData[self::COLUMN_ENTITY_TYPE]) &&
            $rowData[self::COLUMN_ENTITY_TYPE] == 'category'
        ) {
            if (empty($rowData[self::COLUMN_TARGET_PATH])) {
                $rowData[self::COLUMN_TARGET_PATH] = $this->_getCanonicalUrlPath($rowData);
            }
        }
        unset($rowData[self::COLUMN_CATEGORY_NAME]);
        return $rowData;
    }

    /**
     * Prepare row data for replace behaviour
     *
     * @param array $rowData
     * @return array
     */
    public function prepareRowForReplace(array $rowData)
    {
        return $this->prepareRowForUpdate($rowData);
    }

    /**
     * Prepare row data for delete behaviour
     *
     * @param array $rowData
     * @return array
     */
    public function prepareRowForDelete(array $rowData)
    {
        return $this->prepareRowForUpdate($rowData);
    }

    /**
     * Retrieve canonical category url path
     *
     * @param array $rowData
     * @return string
     */
    protected function _getCanonicalUrlPath(array $rowData)
    {
        return 'catalog/category/view/id/' . $rowData[self::COLUMN_ENTITY_ID];
    }

    /**
     * Retrieve category id by name
     *
     * @param array $rowData
     * @return int|null
     */
    protected function _getCategory(array $rowData)
    {
        $collection = $this->_getCategoryCollection();
        $collection->clear()
            ->setStoreId($rowData[self::COLUMN_STORE_ID])
            ->addAttributeToFilter('name', $rowData[self::COLUMN_CATEGORY_NAME])
            ->load();

        /** @var \Magento\Catalog\Model\Category $category */
        $category = $collection->getFirstItem();
        return $category->getId() ?: null;
    }

    /**
     * Retrieve category collection
     *
     * @return \Magento\Catalog\Model\ResourceModel\Category\Collection
     */
    protected function _getCategoryCollection()
    {
        if (null === $this->_collection) {
            $this->_collection = $this->_collectionFactory->create();
        }
        return $this->_collection;
    }
}
