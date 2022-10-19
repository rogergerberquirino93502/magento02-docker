<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Import\Order\Tax;

use Firebear\ImportExport\Model\Import\Order\AbstractAdapter;
use Magento\Framework\DB\Select;

/**
 * Order Tax Item Import
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
    const PREFIX = 'tax_item';

    /**
     * Entity Id Column Name
     *
     */
    const COLUMN_ENTITY_ID = 'tax_item_id';

    /**
     * Tax Id Column Name
     *
     */
    const COLUMN_TAX_ID = 'tax_id';

    /**
     * Order Item Id Column Name
     *
     */
    const COLUMN_ORDER_ITEM_ID = 'item_id';

    /**
     * Percent Column Name
     *
     */
    const COLUMN_PERCENT = 'tax_percent';

    /**
     * Taxable Item Type Column Name
     */
    const COLUMN_TAXABLE_ITEM_TYPE = 'taxable_item_type';

    /**
     * Associated Item Id Column Name
     *
     */
    const COLUMN_ASSOCIATED_ITEM_ID = 'associated_item_id';

    /**
     * Error Codes
     */
    const ERROR_ENTITY_ID_IS_EMPTY = 'taxItemIdIsEmpty';
    const ERROR_TAX_ID_IS_EMPTY = 'taxItemParentIdIsEmpty';
    const ERROR_PERCENT_IS_EMPTY = 'taxItemPercentIsEmpty';
    const ERROR_DUPLICATE_ENTITY_ID = 'duplicateTaxItemId';
    const ERROR_ORDER_ITEM_ID_IS_EMPTY = 'taxItemOrderItemIdIsEmpty';

    /**
     * Validation Failure Message Template Definitions
     *
     * @var array
     */
    protected $_messageTemplates = [
        self::ERROR_DUPLICATE_ENTITY_ID => 'Tax Item tax_item_id is found more than once in the import file',
        self::ERROR_ENTITY_ID_IS_EMPTY => 'Tax Item tax_item_id is empty',
        self::ERROR_PERCENT_IS_EMPTY => 'Tax Item tax_percent is empty',
        self::ERROR_TAX_ID_IS_EMPTY => 'Tax Item tax_id is empty',
        self::ERROR_ORDER_ITEM_ID_IS_EMPTY => 'Tax Item item_id is empty',
    ];

    /**
     * Order Tax Table Name
     *
     * @var string
     */
    protected $_mainTable = 'sales_order_tax_item';

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
            ':tax_id' => $this->_getTaxId($rowData),
            ':tax_percent' => $rowData[self::COLUMN_PERCENT],
        ];

        $itemId = $this->_getOrderItemId($rowData);
        if ($itemId) {
            $bind[':item_id'] = $itemId;
        }

        /** @var $select Select */
        $select = $this->_connection->select();
        $select->from($this->getMainTable(), self::COLUMN_ENTITY_ID)
            ->where('tax_id = :tax_id')
            ->where('tax_percent = :tax_percent');

        if ($itemId) {
            $select->where('item_id = :item_id');
        } else {
            $select->where('item_id IS NULL');
        }

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

        $associatedItemId = null;
        if (!empty($rowData[self::COLUMN_ASSOCIATED_ITEM_ID])) {
            if (isset($this->itemIdsMap[$rowData[self::COLUMN_ASSOCIATED_ITEM_ID]])) {
                $associatedItemId = $this->itemIdsMap[$rowData[self::COLUMN_ASSOCIATED_ITEM_ID]];
            }
        }

        $newEntity = false;
        $entityId = $this->_getExistEntityId($rowData);
        if (!$entityId) {
            /* create new entity id */
            $newEntity = true;
            $entityId = $this->_getNextEntityId();
            $this->_newEntities[$rowData[self::COLUMN_ENTITY_ID]] = $entityId;
        }

        $entityRow = [
            self::COLUMN_ENTITY_ID => $entityId,
            self::COLUMN_TAX_ID => $this->_getTaxId($rowData),
            self::COLUMN_ORDER_ITEM_ID => $this->_getOrderItemId($rowData),
            self::COLUMN_ASSOCIATED_ITEM_ID => $associatedItemId
        ];

        /* prepare data */
        $entityRow = $this->_prepareEntityRow($entityRow, $rowData);
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
     * Validate Row Data For Add/Update Behaviour
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return void
     */
    protected function _validateRowForUpdate(array $rowData, $rowNumber)
    {
        if ($this->_checkEntityIdKey($rowData, $rowNumber)) {
            if (empty($rowData[self::COLUMN_TAX_ID])) {
                $this->addRowError(self::ERROR_TAX_ID_IS_EMPTY, $rowNumber);
            }

            if (empty($rowData[self::COLUMN_ORDER_ITEM_ID])
                && $rowData[self::COLUMN_TAXABLE_ITEM_TYPE] !== 'shipping'
            ) {
                $this->addRowError(self::ERROR_ORDER_ITEM_ID_IS_EMPTY, $rowNumber);
            }

            if (empty($rowData[self::COLUMN_PERCENT])) {
                $this->addRowError(self::ERROR_PERCENT_IS_EMPTY, $rowNumber);
            }
        }
    }
}
