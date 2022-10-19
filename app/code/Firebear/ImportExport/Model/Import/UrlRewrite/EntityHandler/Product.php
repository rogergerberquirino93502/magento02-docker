<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Import\UrlRewrite\EntityHandler;

use Magento\Catalog\Api\Data\ProductInterface;
use Firebear\ImportExport\Model\Import\UrlRewrite\AbstractEntityHandler;
use Firebear\ImportExport\Model\Import\UrlRewrite\EntityHandlerInterface;

/**
 * Product handler
 */
class Product extends AbstractEntityHandler implements EntityHandlerInterface
{
    const ENTITY_TYPE = 'product';

    /**
     * Entity Id column name
     */
    const COLUMN_ENTITY_ID = 'entity_id';

    /**
     * Entity type column name
     */
    const COLUMN_ENTITY_TYPE = 'entity_type';

    /**
     * Sku column name
     */
    const COLUMN_SKU = 'sku';

    /**
     * Target path column name
     */
    const COLUMN_TARGET_PATH = 'target_path';

    /**
     * Url rewrite_id column name
     */
    const COLUMN_URL_REWRITE_ID = 'url_rewrite_id';

    /**
     * Product instance
     *
     * @var \Magento\Catalog\Model\Product
     */
    protected $_product;

    /**
     * Error codes
     */
    const ERROR_SKU_NOT_FOUND = 'urlRewriteSkuNotFound';
    const ERROR_URL_REWRITE_ID_IS_EMPTY = 'urlRewriteIdIsEmpty';
    const ERROR_ERROR_ENTITY_NOT_FOUND = 'urlRewriteEntityNotFound';

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_messageTemplates = [
        self::ERROR_SKU_NOT_FOUND => 'Product with specified SKU not found',
        self::ERROR_URL_REWRITE_ID_IS_EMPTY => 'Url rewrite id is empty',
        self::ERROR_ERROR_ENTITY_NOT_FOUND => 'Product with specified entity id not found',
    ];

    /**
     * Initialize import
     *
     * @param ProductInterface $product
     */
    public function __construct(
        ProductInterface $product
    ) {
        $this->_product = $product;
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
        if (!empty($rowData[self::COLUMN_SKU])) {
            $entityId = $this->_product->getIdBySku($rowData[self::COLUMN_SKU]);
            if (!$entityId) {
                $this->addRowError(self::ERROR_SKU_NOT_FOUND, $rowNumber);
            }
        }
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
        if (!empty($rowData[self::COLUMN_SKU])) {
            $entityId = $this->_product->getIdBySku($rowData[self::COLUMN_SKU]);
            if (!$entityId) {
                $this->addRowError(self::ERROR_SKU_NOT_FOUND, $rowNumber);
            }
        } elseif (!empty($rowData[self::COLUMN_ENTITY_ID]) &&
            !$this->_product->isProductsHasSku([$rowData[self::COLUMN_ENTITY_ID]])) {
            $this->addRowError(self::ERROR_ERROR_ENTITY_NOT_FOUND, $rowNumber);
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
        if (!empty($rowData[self::COLUMN_SKU])) {
            $entityId = $this->_product->getIdBySku($rowData[self::COLUMN_SKU]);
            if ($entityId) {
                $rowData[self::COLUMN_ENTITY_ID] = $entityId;
            }
            if (empty($rowData[self::COLUMN_ENTITY_TYPE])) {
                $rowData[self::COLUMN_ENTITY_TYPE] = 'product';
            }
        }

        if (!empty($rowData[self::COLUMN_ENTITY_TYPE]) &&
            $rowData[self::COLUMN_ENTITY_TYPE] == 'product'
        ) {
            if (empty($rowData[self::COLUMN_TARGET_PATH])) {
                $rowData[self::COLUMN_TARGET_PATH] = $this->_getCanonicalUrlPath($rowData);
            }
        }
        unset($rowData[self::COLUMN_SKU]);
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
     * Retrieve canonical product url path
     *
     * @param array $rowData
     * @return string
     */
    protected function _getCanonicalUrlPath(array $rowData)
    {
        return 'catalog/product/view/id/' . $rowData[self::COLUMN_ENTITY_ID];
    }
}
