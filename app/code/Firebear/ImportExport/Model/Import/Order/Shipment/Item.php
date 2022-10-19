<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Import\Order\Shipment;

use Magento\ImportExport\Model\Import;
use Firebear\ImportExport\Model\Import\Order\AbstractAdapter;

/**
 * Order Shipment Item Import
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
    const PREFIX = 'shipment_item';

    /**
     * Entity Id Column Name
     *
     */
    const COLUMN_ENTITY_ID = 'entity_id';

    /**
     * Shipment Id Column Name
     *
     */
    const COLUMN_SHIPMENT_ID = 'parent_id';

    /**
     * Order Item Id Column Name
     *
     */
    const COLUMN_ORDER_ITEM_ID = 'order_item_id';

    /**
     * Shipment Increment Id Column Name
     *
     */
    const COLUMN_SHIPMENT_INCREMENT_ID = 'shipment:increment_id';

    /**
     * Error Codes
     */
    const ERROR_ENTITY_ID_IS_EMPTY = 'shipmentItemIdIsEmpty';
    const ERROR_SHIPMENT_ID_IS_EMPTY = 'shipmentItemParentIdIsEmpty';
    const ERROR_DUPLICATE_ENTITY_ID = 'duplicateShipmentItemId';
    const ERROR_ORDER_ITEM_ID_IS_EMPTY = 'shipmentItemOrderItemIdIsEmpty';
    const ERROR_SHIPMENT_INCREMENT_ID = 'shipmentItemIncrementId';

    /**
     * Validation Failure Message Template Definitions
     *
     * @var array
     */
    protected $_messageTemplates = [
        self::ERROR_DUPLICATE_ENTITY_ID => 'Shipment Item entity_id is found more than once in the import file',
        self::ERROR_ENTITY_ID_IS_EMPTY => 'Shipment Item entity_id is empty',
        self::ERROR_SHIPMENT_ID_IS_EMPTY => 'Shipment Item parent_id is empty',
        self::ERROR_ORDER_ITEM_ID_IS_EMPTY => 'Shipment Item order_item_id is empty',
        self::ERROR_SHIPMENT_INCREMENT_ID => 'Shipment with selected shipment:increment_id does not exist',
    ];

    /**
     * Shipment Ids
     *
     * @var array
     */
    protected $shipmentIds;

    /**
     * Order Shipment Item Table Name
     *
     * @var string
     */
    protected $_mainTable = 'sales_shipment_item';

    /**
     * Current Shipment increment id;
     *
     * @var string
     */
    protected $_currentShipmentId;

    /**
     * Retrieve The Prepared Data
     *
     * @param array $rowData
     * @return array|bool
     */
    public function prepareRowData(array $rowData)
    {
        $this->prepareCurrentOrderId($rowData);
        if (!empty($rowData[self::COLUMN_SHIPMENT_INCREMENT_ID])) {
            $this->_currentShipmentId = $rowData[self::COLUMN_SHIPMENT_INCREMENT_ID];
        }
        $rowData = $this->_extractField($rowData, static::PREFIX);
        return (count($rowData) && !$this->isEmptyRow($rowData))
            ? $rowData
            : false;
    }

    /**
     * Import Data Rows
     *
     * @return boolean
     */
    protected function _importData()
    {
        parent::_importData();
        $this->_updateShipment();

        return true;
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
            ':parent_id' => $this->_getShipmentId($rowData)
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

        if ($this->_currentShipmentId && empty($rowData[self::COLUMN_SHIPMENT_ID])) {
            $shipmentId = $this->_getExistShipmentId();
            if (empty($this->shipmentIdsMap[$shipmentId])) {
                $this->shipmentIdsMap[$shipmentId] = $shipmentId;
            }
            $rowData[self::COLUMN_SHIPMENT_ID] = $shipmentId;
        }

        if (!empty($rowData[self::COLUMN_SHIPMENT_ID]) &&
            empty($rowData[self::COLUMN_ORDER_ITEM_ID])
        ) {
            $orderId = $this->_getOrderIdByShipment($rowData);
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

        $shipmentId = $this->_getShipmentId($rowData);
        $orderItemId = $this->_getOrderItemId($rowData);

        $entityRow = [
            'entity_id' => $entityId,
            'parent_id' => $shipmentId,
            'order_item_id' => $orderItemId
        ];
        /* prepare data */
        $key = $rowData[self::COLUMN_ENTITY_ID] ?? $entityId;
        $this->shipmentIds[$shipmentId][$key] = $entityRow;
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
        if (!empty($this->_currentShipmentId) && empty($rowData[self::COLUMN_SHIPMENT_ID])) {
            if (!$this->_getExistShipmentId()) {
                $this->addRowError(self::ERROR_SHIPMENT_INCREMENT_ID, $rowNumber);
            }
        } elseif ($this->_checkEntityIdKey($rowData, $rowNumber)) {
            if (empty($rowData[self::COLUMN_SHIPMENT_ID])) {
                $this->addRowError(self::ERROR_SHIPMENT_ID_IS_EMPTY, $rowNumber);
            }

            if (empty($rowData[self::COLUMN_ORDER_ITEM_ID])) {
                $this->addRowError(self::ERROR_ORDER_ITEM_ID_IS_EMPTY, $rowNumber);
            }
        }
    }

    /**
     * Update And Insert Data In Shipment Entity Table
     *
     * @return $this
     */
    protected function _updateShipment()
    {
        $toParent = $this->shipmentIds;
        if ($toParent) {
            $select = $this->_connection->select()->from(
                $this->getShipmentTable(),
                ['entity_id', 'packages']
            )->where(
                'entity_id IN (?)',
                array_keys($toParent)
            );

            foreach ($this->_connection->fetchAll($select) as $shipment) {
                if (empty($shipment['packages'])) {
                    continue;
                }

                $packages = json_decode($shipment['packages'], true);
                if (!is_array($packages)) {
                    continue;
                }

                foreach ($packages as $key => $package) {
                    if (empty($package['items']) || !is_array($package['items'])) {
                        continue;
                    }
                    foreach ($package['items'] as $oldItemId => $info) {
                        if (isset($toParent[$shipment['entity_id']][$oldItemId]) &&
                            isset($info['order_item_id'])
                        ) {
                            $newData = $packages[$key]['items'][$oldItemId];
                            $data = $toParent[$shipment['entity_id']][$oldItemId];
                            unset($packages[$key]['items'][$oldItemId]);
                            $newData['order_item_id'] = $data['order_item_id'];
                            $packages[$key]['items'][$data['entity_id']] = $newData;
                        }
                    }
                }

                $bind = ['packages' => json_encode($packages)];
                $where = ['entity_id = ?' => $shipment['entity_id']];
                $this->_connection->update($this->getShipmentTable(), $bind, $where);
            }
        }
        return $this;
    }

    /**
     * Retrieve Shipment Id If Shipment Is Present In Database
     *
     * @return bool|int
     */
    protected function _getExistShipmentId()
    {
        $bind = [':increment_id' => $this->_currentShipmentId];
        /** @var $select \Magento\Framework\DB\Select */
        $select = $this->_connection->select();
        $select->from($this->getShipmentTable(), 'entity_id')
            ->where('increment_id = :increment_id');

        return $this->_connection->fetchOne($select, $bind);
    }

    /**
     * Retrieve Order Id If Shipment Is Present In Database
     *
     * @param array $rowData
     * @return bool|int
     */
    protected function _getOrderIdByShipment(array $rowData)
    {
        $bind = [':entity_id' => $rowData[self::COLUMN_SHIPMENT_ID]];
        /** @var $select \Magento\Framework\DB\Select */
        $select = $this->_connection->select();
        $select->from($this->getShipmentTable(), 'order_id')
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
     * Retrieve Order Item Table Name
     *
     * @return string
     */
    public function getOrderItemTable()
    {
        return $this->_resource->getTableName('sales_order_item');
    }
}
