<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Import\Order;

use Magento\ImportExport\Model\Import;

/**
 * Order Item Import
 */
class Item extends AbstractAdapter
{
    /**
     * Entity Type Code
     *
     */
    const ENTITY_TYPE_CODE = 'order';

    /**
     * Prefix of Fields
     *
     */
    const PREFIX = 'item';

    /**
     * Entity Id Column Name
     *
     */
    const COLUMN_ENTITY_ID = 'item_id';

    /**
     * Quote Item Id Column Name
     *
     */
    const COLUMN_QUOTE_ITEM_ID = 'quote_item_id';

    /**
     * Product Id Column Name
     *
     */
    const COLUMN_PRODUCT_ID = 'product_id';

    /**
     * Product Options Column Name
     *
     */
    const COLUMN_PRODUCT_OPTIONS = 'product_options';

    /**
     * Parent Item Id Column Name
     *
     */
    const COLUMN_PARENT_ITEM_ID = 'parent_item_id';

    /**
     * Created At Column Name
     *
     */
    const COLUMN_CREATED_AT = 'created_at';

    /**
     * Updated At Column Name
     *
     */
    const COLUMN_UPDATED_AT = 'updated_at';

    /**
     * Sku Column Name
     *
     */
    const COLUMN_SKU = 'sku';

    /**
     * Error Codes
     */
    const ERROR_ENTITY_ID_IS_EMPTY = 'orderItemIdIsEmpty';
    const ERROR_COLUMN_SKU_IS_EMPTY = 'orderItemSkuIsEmpty';
    const ERROR_DUPLICATE_ENTITY_ID = 'duplicateOrderItemId';

    /**
     * Validation Failure Message Template Definitions
     *
     * @var array
     */
    protected $_messageTemplates = [
        self::ERROR_DUPLICATE_ENTITY_ID => 'Order Item item_id is found more than once in the import file',
        self::ERROR_COLUMN_SKU_IS_EMPTY => 'Order Item product sku is empty',
        self::ERROR_ENTITY_ID_IS_EMPTY => 'Order Item entity_id is empty'
    ];

    /**
     * Order Item Table Name
     *
     * @var string
     */
    protected $_mainTable = 'sales_order_item';

    /**
     *
     *
     * @var array
     */
    protected $_baseFields = [
        'cost',
        'price',
        'original_price',
        'tax_amount',
        'tax_invoiced',
        'discount_amount',
        'discount_invoiced',
        'amount_refunded',
        'row_total',
        'row_invoiced',
        'tax_before_discount',
        'row_total_incl_tax',
        'discount_tax_compensation_amount',
        'discount_tax_compensation_invoiced',
        'discount_tax_compensation_refunded',
        'tax_refunded',
        'discount_refunded',
        'weee_tax_applied_amount',
        'weee_tax_applied_row_amnt',
        'weee_tax_disposition',
        'weee_tax_row_disposition',
    ];

    /**
     *
     *
     * @var array
     */
    protected $_taxFields = [
        'price',
        'base_price',
        'row_total',
        'base_row_total',
    ];

    /**
     * Retrieve The Prepared Data
     *
     * @param array $rowData
     * @return array|bool
     */
    public function prepareRowData(array $rowData)
    {
        $this->prepareCurrentOrderId($rowData);
        $rowData = $this->_extractField($rowData, static::PREFIX);
        return (count($rowData) && !$this->isEmptyRow($rowData))
            ? $rowData
            : false;
    }

    /**
     * Retrieve Entity Id If Entity Is Present In Database
     *
     * @param array $rowData
     * @return bool|int
     */
    protected function _getExistEntityId(array $rowData)
    {
        $bind = [
            ':sku' => $rowData[self::COLUMN_SKU],
            ':product_options' => $rowData[self::COLUMN_PRODUCT_OPTIONS] ?? '',
            ':order_id' => $this->_getOrderId($rowData)
        ];
        $select = $this->_connection->select();
        $select->from($this->getMainTable(), 'item_id')
            ->where('sku = :sku')
            ->where('product_options = :product_options')
            ->where('order_id = :order_id');

        return $this->_connection->fetchOne($select, $bind);
    }

    /**
     * Prepare Data For Update
     *
     * @param array $rowData
     * @return array
     */
    protected function _prepareDataForUpdate(array $rowData)
    {
        $toCreate = [];
        $toUpdate = [];

        list($createdAt, $updatedAt) = $this->_prepareDateTime($rowData);

        $quoteItemId = null;
        if (!empty($rowData[self::COLUMN_QUOTE_ITEM_ID])) {
            $quoteItemId = $rowData[self::COLUMN_QUOTE_ITEM_ID];
        }

        $parentItemId = null;
        if (!empty($rowData[self::COLUMN_PARENT_ITEM_ID])) {
            if (isset($this->itemIdsMap[$rowData[self::COLUMN_PARENT_ITEM_ID]])) {
                $parentItemId = $this->itemIdsMap[$rowData[self::COLUMN_PARENT_ITEM_ID]];
            }
        }

        $productId = $this->getProductIdBySku($rowData[self::COLUMN_SKU]) ?: null;

        $newEntity = false;
        $entityId = $this->_getExistEntityId($rowData);
        $orderId = $this->_getOrderId($rowData);

        if (!$entityId) {
            /* create new entity id */
            $newEntity = true;
            $entityId = $this->_getNextEntityId();
            $this->_newEntities[$rowData[self::COLUMN_ENTITY_ID]] = $entityId;
        }

        $this->itemIdsMap[$rowData[self::COLUMN_ENTITY_ID]] = $entityId;

        if (!isset($rowData['base_price'])) {
            foreach ($this->_baseFields as $field) {
                if (isset($rowData[$field]) && !isset($rowData['base_' . $field])) {
                    $rowData['base_' . $field] = $rowData[$field];
                } elseif (!isset($rowData[$field])) {
                    // set default values
                    $rowData[$field] = $rowData['base_' . $field] = 0;
                }
            }
        }

        if (empty($rowData['tax_amount'])) {
            foreach ($this->_taxFields as $field) {
                if (isset($rowData[$field])) {
                    $rowData[$field . '_incl_tax'] = $rowData[$field];
                }
            }
        }

        $entityRow = [
            self::COLUMN_CREATED_AT => $createdAt,
            self::COLUMN_UPDATED_AT => $updatedAt,
            self::COLUMN_QUOTE_ITEM_ID => $quoteItemId,
            'order_id' => $orderId,
            self::COLUMN_ENTITY_ID => $entityId,
            self::COLUMN_PARENT_ITEM_ID => $parentItemId,
            self::COLUMN_PRODUCT_ID => $productId,
            self::COLUMN_SKU => $rowData[self::COLUMN_SKU]
        ];
        /* prepare data */
        $entityRow = $this->_prepareEntityRow($entityRow, $rowData);
        if (!empty($rowData['downloadable_link_data'])) {
            $downloadableLinkData = $this->prepareDownloadableLinkData($rowData);
            $entityRow['downloadable_link_data'] = $downloadableLinkData;
        }
        if ($newEntity) {
            $toCreate[] = $entityRow;
        } else {
            $toUpdate[] = $entityRow;
        }
        return [
            self::ENTITIES_TO_CREATE_KEY => $toCreate,
            self::ENTITIES_TO_UPDATE_KEY => $toUpdate
        ];
    }

    /**
     * Preparing Downloadable Link Data
     *
     * @param $rowData
     * @return array[]
     */
    protected function prepareDownloadableLinkData($rowData)
    {
        $allDownloadableLinkData = $this->jsonHelper->jsonDecode($rowData['downloadable_link_data']);
        $downloadableLinkData = [];

        $downloadableLinkPurchasedFields = array_keys(
            $this->_connection->describeTable($this->_resource->getTableName('downloadable_link_purchased'))
        );
        $downloadableLinkPurchasedItemFields = array_keys(
            $this->_connection->describeTable(
                $this->_resource->getTableName('downloadable_link_purchased_item')
            )
        );

        foreach ($allDownloadableLinkData as $key => $linkData) {
            foreach ($downloadableLinkPurchasedFields as $field) {
                switch ($field) {
                    case 'order_item_id':
                        if (!empty($linkData[$field])) {
                            $downloadableLinkPurchasedData[$key][$field] = $this->itemIdsMap[$linkData[$field]] ??
                                $linkData[$field];
                        }
                        break;
                    case 'order_id':
                        if (!empty($linkData[$field]) && !empty($linkData['order_increment_id'])) {
                            $downloadableLinkPurchasedData[$key][$field] =
                                $this->orderIdsMap[$linkData['order_increment_id']] ?? $linkData[$field];
                        }
                        break;
                    default:
                        $downloadableLinkPurchasedData[$key][$field] = $linkData[$field] ?? '';
                }
            }
            foreach ($downloadableLinkPurchasedItemFields as $field) {
                if ($field == 'order_item_id' && !empty($linkData[$field])) {
                    $downloadableLinkPurchasedItemData[$key][$field] = $this->itemIdsMap[$linkData[$field]] ??
                        $linkData[$field];
                } else {
                    $downloadableLinkPurchasedItemData[$key][$field] = $linkData[$field] ?? '';
                }
            }
        }
        if (!empty($downloadableLinkPurchasedData) && !empty($downloadableLinkPurchasedItemData)) {
            $downloadableLinkData = [
                'downloadable_link_purchased' => $downloadableLinkPurchasedData,
                'downloadable_link_purchased_item' => $downloadableLinkPurchasedItemData
            ];
        }
        return $downloadableLinkData;
    }

    /**
     * {@inheritdoc}
     */
    protected function _saveEntities(array $toCreate, array $toUpdate)
    {
        $toSaveDownloadableLinkData = $this->extractDownloadableLinkData($toCreate, $toUpdate);
        parent::_saveEntities($toCreate, $toUpdate);
        if (!empty($toSaveDownloadableLinkData)) {
            $this->saveDownloadableLinkData($toSaveDownloadableLinkData);
        }
        return $this;
    }

    /**
     * Extract downloadable link data
     *
     * @param $toCreate
     * @param $toUpdate
     * @return array
     */
    protected function extractDownloadableLinkData(&$toCreate, &$toUpdate)
    {
        $toSaveDownloadableLinkData = [];
        if (!empty($toCreate)) {
            foreach ($toCreate as &$rowToCreate) {
                if (!empty($rowToCreate['downloadable_link_data'])) {
                    $toSaveDownloadableLinkData[] = $rowToCreate['downloadable_link_data'];
                    unset($rowToCreate['downloadable_link_data']);
                }
            }
        }
        if (!empty($toUpdate)) {
            foreach ($toUpdate as &$rowToUpdate) {
                if (!empty($rowToUpdate['downloadable_link_data'])) {
                    $toSaveDownloadableLinkData[] = $rowToUpdate['downloadable_link_data'];
                    unset($rowToUpdate['downloadable_link_data']);
                }
            }
        }
        return $toSaveDownloadableLinkData;
    }

    /**
     * Save a data of the downloadable link
     *
     * @param $toSaveDownloadableLinkData
     */
    protected function saveDownloadableLinkData($toSaveDownloadableLinkData)
    {
        foreach ($toSaveDownloadableLinkData as $toSave) {
            $downloadableLinkPurchasedData = $toSave['downloadable_link_purchased'] ?? null;
            $downloadableLinkPurchasedItemData = $toSave['downloadable_link_purchased_item'] ?? null;

            try {
                if (!empty($downloadableLinkPurchasedData)) {
                    $this->_connection->insertOnDuplicate(
                        $this->_connection->getTableName('downloadable_link_purchased'),
                        $downloadableLinkPurchasedData
                    );
                }
                if (!empty($downloadableLinkPurchasedItemData)) {
                    $this->_connection->insertOnDuplicate(
                        $this->_connection->getTableName('downloadable_link_purchased_item'),
                        $downloadableLinkPurchasedItemData
                    );
                }
            } catch (\Exception $e) {
                $this->addLogWriteln(
                    __(
                        'An error occurred with saving downloadable link data'
                    ),
                    $this->getOutput(),
                    'error'
                );
            }
        }
    }

    /**
     * Validate Row Data For Add/Update Behaviour
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return void
     */
    protected function _validateRowForUpdate(array $rowData, $rowNumber)
    {
        if ($this->_checkEntityIdKey($rowData, $rowNumber)) {
            if (empty($rowData[self::COLUMN_SKU])) {
                $this->addRowError(self::ERROR_COLUMN_SKU_IS_EMPTY, $rowNumber);
            }
        }
    }
}
