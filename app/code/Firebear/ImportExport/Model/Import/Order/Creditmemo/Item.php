<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Import\Order\Creditmemo;

use Magento\ImportExport\Model\Import;
use Firebear\ImportExport\Model\Import\Order\AbstractAdapter;

/**
 * Order Creditmemo Item Import
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
    const PREFIX = 'creditmemo_item';

    /**
     * Entity Id Column Name
     *
     */
    const COLUMN_ENTITY_ID = 'entity_id';

    /**
     * Creditmemo Id Column Name
     *
     */
    const COLUMN_CREDITMEMO_ID = 'parent_id';

    /**
     * Order Item Id Column Name
     *
     */
    const COLUMN_ORDER_ITEM_ID = 'order_item_id';

    /**
     * Creditmemo Increment Id Column Name
     *
     */
    const COLUMN_CREDITMEMO_INCREMENT_ID = 'creditmemo:increment_id';

    /**
     * Error Codes
     */
    const ERROR_ENTITY_ID_IS_EMPTY = 'creditmemoItemIdIsEmpty';
    const ERROR_CREDITMEMO_ID_IS_EMPTY = 'creditmemoItemParentIdIsEmpty';
    const ERROR_DUPLICATE_ENTITY_ID = 'duplicateCreditmemoItemId';
    const ERROR_ORDER_ITEM_ID_IS_EMPTY = 'creditmemoItemOrderItemIdIsEmpty';
    const ERROR_CREDITMEMO_INCREMENT_ID = 'creditmemoItemIncrementId';

    /**
     * Validation Failure Message Template Definitions
     *
     * @var array
     */
    protected $_messageTemplates = [
        self::ERROR_DUPLICATE_ENTITY_ID => 'Creditmemo Item entity_id is found more than once in the import file',
        self::ERROR_ENTITY_ID_IS_EMPTY => 'Creditmemo Item entity_id is empty',
        self::ERROR_CREDITMEMO_ID_IS_EMPTY => 'Creditmemo Item parent_id is empty',
        self::ERROR_ORDER_ITEM_ID_IS_EMPTY => 'Creditmemo Item order_item_id is empty',
        self::ERROR_CREDITMEMO_INCREMENT_ID => 'Creditmemo with selected creditmemo:increment_id does not exist',
    ];

    /**
     * Order Creditmemo Item Table Name
     *
     * @var string
     */
    protected $_mainTable = 'sales_creditmemo_item';

    /**
     * Order Creditmemo Table Name
     *
     * @var string
     */
    protected $_creditmemoTable = 'sales_creditmemo';

    /**
     * Current Creditmemo increment id;
     *
     * @var string
     */
    protected $_currentCreditmemoId;

    /**
     * Retrieve The Prepared Data
     *
     * @param array $rowData
     * @return array|bool
     */
    public function prepareRowData(array $rowData)
    {
        $this->prepareCurrentOrderId($rowData);
        if (!empty($rowData[self::COLUMN_CREDITMEMO_INCREMENT_ID])) {
            $this->_currentCreditmemoId = $rowData[self::COLUMN_CREDITMEMO_INCREMENT_ID];
        }
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
            ':parent_id' => $this->_getCreditmemoId($rowData)
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

        if ($this->_currentCreditmemoId && empty($rowData[self::COLUMN_CREDITMEMO_ID])) {
            $creditmemoId = $this->_getExistCreditmemoId();
            if (empty($this->creditmemoIdsMap[$creditmemoId])) {
                $this->creditmemoIdsMap[$creditmemoId] = $creditmemoId;
            }
            $rowData[self::COLUMN_CREDITMEMO_ID] = $creditmemoId;
        }

        if (!empty($rowData[self::COLUMN_CREDITMEMO_ID]) &&
            empty($rowData[self::COLUMN_ORDER_ITEM_ID])
        ) {
            $orderId = $this->_getOrderIdByCreditmemo($rowData);
            if (empty($this->orderIdsMap[$orderId])) {
                $this->orderIdsMap[$orderId] = $orderId;
            }
            $this->_currentOrderId = $orderId;
            $orderItemId = $this->_getOrderItemIdByOrder($rowData);
            if (empty($this->itemIdsMap[$orderItemId])) {
                $this->itemIdsMap[$orderItemId] = $orderItemId;
            }
            $rowData[self::COLUMN_ORDER_ITEM_ID] = $orderItemId;
        }

        $newEntity = false;
        $entityId = $this->_getExistEntityId($rowData);
        if (!$entityId) {
            /* create new entity id */
            $newEntity = true;
            $entityId = $this->_getNextEntityId();
            $key = $rowData[self::COLUMN_ENTITY_ID] ?? $entityId;
            $this->_newEntities[$key] = $entityId;
        }

        $entityRow = [
            'entity_id' => $entityId,
            'parent_id' => $this->_getCreditmemoId($rowData),
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
        if (empty($this->_currentCreditmemoId) && $this->_checkEntityIdKey($rowData, $rowNumber)) {
            if (empty($rowData[self::COLUMN_CREDITMEMO_ID])) {
                $this->addRowError(self::ERROR_CREDITMEMO_ID_IS_EMPTY, $rowNumber);
            }

            if (empty($rowData[self::COLUMN_ORDER_ITEM_ID])) {
                $this->addRowError(self::ERROR_ORDER_ITEM_ID_IS_EMPTY, $rowNumber);
            }
        }
    }

    /**
     * Retrieve Creditmemo Id If Creditmemo Is Present In Database
     *
     * @return bool|int
     */
    protected function _getExistCreditmemoId()
    {
        $bind = [':increment_id' => $this->_currentCreditmemoId];
        /** @var $select \Magento\Framework\DB\Select */
        $select = $this->_connection->select();
        $select->from($this->getCreditmemoTable(), 'entity_id')
            ->where('increment_id = :increment_id');

        return $this->_connection->fetchOne($select, $bind);
    }

    /**
     * Retrieve Order Id If Creditmemo Is Present In Database
     *
     * @param array $rowData
     * @return bool|int
     */
    protected function _getOrderIdByCreditmemo(array $rowData)
    {
        $bind = [':entity_id' => $rowData[self::COLUMN_CREDITMEMO_ID]];
        /** @var $select \Magento\Framework\DB\Select */
        $select = $this->_connection->select();
        $select->from($this->getCreditmemoTable(), 'order_id')
            ->where('entity_id = :entity_id');

        return $this->_connection->fetchOne($select, $bind);
    }

    /**
     * Retrieve Order Item Id
     *
     * @param array $rowData
     * @return bool|int
     */
    protected function _getOrderItemIdByOrder(array $rowData)
    {
        $bind = [
            ':order_id' => $this->_currentOrderId,
            ':sku' => $rowData['sku'],
        ];
        /** @var $select \Magento\Framework\DB\Select */
        $select = $this->_connection->select();
        $select->from($this->getOrderItemTable(), 'item_id')
            ->where('order_id = :order_id')
            ->where('sku = :sku');

        return $this->_connection->fetchOne($select, $bind);
    }

    /**
     * Retrieve Creditmemo Table Name
     *
     * @return string
     */
    public function getCreditmemoTable()
    {
        return $this->_resource->getTableName(
            $this->_creditmemoTable
        );
    }

    /**
     * Retrieve Order Item Table Name
     *
     * @return string
     */
    public function getOrderItemTable()
    {
        return $this->_resource->getTableName('sales_order_item');
    }
}
