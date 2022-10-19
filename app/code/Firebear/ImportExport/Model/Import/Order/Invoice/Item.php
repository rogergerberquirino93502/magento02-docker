<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Import\Order\Invoice;

use Magento\ImportExport\Model\Import;
use Firebear\ImportExport\Model\Import\Order\AbstractAdapter;

/**
 * Order Invoice Item Import
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
    const PREFIX = 'invoice_item';

    /**
     * Entity Id Column Name
     *
     */
    const COLUMN_ENTITY_ID = 'entity_id';

    /**
     * Invoice Id Column Name
     *
     */
    const COLUMN_INVOICE_ID = 'parent_id';

    /**
     * Order Item Id Column Name
     *
     */
    const COLUMN_ORDER_ITEM_ID = 'order_item_id';

    /**
     * Error Codes
     */
    const ERROR_ENTITY_ID_IS_EMPTY = 'invoiceItemIdIsEmpty';
    const ERROR_INVOICE_ID_IS_EMPTY = 'invoiceItemParentIdIsEmpty';
    const ERROR_DUPLICATE_ENTITY_ID = 'duplicateInvoiceItemId';
    const ERROR_ORDER_ITEM_ID_IS_EMPTY = 'invoiceItemOrderItemIdIsEmpty';

    /**
     * Validation Failure Message Template Definitions
     *
     * @var array
     */
    protected $_messageTemplates = [
        self::ERROR_DUPLICATE_ENTITY_ID => 'Invoice Item entity_id is found more than once in the import file',
        self::ERROR_ENTITY_ID_IS_EMPTY => 'Invoice Item entity_id is empty',
        self::ERROR_INVOICE_ID_IS_EMPTY => 'Invoice Item parent_id is empty',
        self::ERROR_ORDER_ITEM_ID_IS_EMPTY => 'Invoice Item order_item_id is empty',
    ];

    /**
     * Order Invoice Table Name
     *
     * @var string
     */
    protected $_mainTable = 'sales_invoice_item';

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
            ':order_item_id' => $this->_getOrderItemId($rowData),
            ':parent_id' => $this->_getInvoiceId($rowData)
        ];
        /** @var $select \Magento\Framework\DB\Select */
        $select = $this->_connection->select();
        $select->from($this->getMainTable(), 'entity_id')
            ->where('parent_id = :parent_id')
            ->where('order_item_id = :order_item_id');

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

        $newEntity = false;
        $entityId = $this->_getExistEntityId($rowData);
        if (!$entityId) {
            /* create new entity id */
            $newEntity = true;
            $entityId = $this->_getNextEntityId();
            $this->_newEntities[$rowData[self::COLUMN_ENTITY_ID]] = $entityId;
        }

        $entityRow = [
            'entity_id' => $entityId,
            'parent_id' => $this->_getInvoiceId($rowData),
            'order_item_id' => $this->_getOrderItemId($rowData)
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
            if (empty($rowData[self::COLUMN_INVOICE_ID])) {
                $this->addRowError(self::ERROR_INVOICE_ID_IS_EMPTY, $rowNumber);
            }

            if (empty($rowData[self::COLUMN_ORDER_ITEM_ID])) {
                $this->addRowError(self::ERROR_ORDER_ITEM_ID_IS_EMPTY, $rowNumber);
            }
        }
    }
}
