<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Import\Order;

use Magento\ImportExport\Model\Import;
use Magento\Store\Model\Store;

/**
 * Order Entity Import
 */
class Entity extends AbstractAdapter
{
    /**
     * Entity Type Code
     *
     */
    const ENTITY_TYPE_CODE = 'order';

    /**
     * Entity Id Column Name
     *
     */
    const COLUMN_ENTITY_ID = 'entity_id';

    /**
     * Customer Id Column Name
     *
     */
    const COLUMN_CUSTOMER_ID = 'customer_id';

    /**
     * Customer Email Column Name
     *
     */
    const COLUMN_CUSTOMER_EMAIL = 'customer_email';

    /**
     * Store Id Column Name
     *
     */
    const COLUMN_STORE_ID = 'store_id';

    /**
     * Error Codes
     */
    const ERROR_DUPLICATE_INCREMENT_ID = 'duplicateOrderIncrementId';
    const ERROR_INCREMENT_ID_IS_EMPTY = 'orderIncrementIdIsEmpty';

    /**
     * Validation Failure Message Template Definitions
     *
     * @var array
     */
    protected $_messageTemplates = [
        self::ERROR_DUPLICATE_INCREMENT_ID => 'Order increment_id is found more than once in the import file',
        self::ERROR_INCREMENT_ID_IS_EMPTY => 'Order increment_id is empty',
    ];

    /**
     * Order Entity Table Name
     *
     * @var string
     */
    protected $_mainTable = 'sales_order';

    /**
     * Order Status Table Name
     *
     * @var string
     */
    protected $_statusTable = 'sales_order_status';

    /**
     * Order Status Collection
     *
     * @var array
     */
    protected $_status;

    /**
     *
     *
     * @var array
     */
    protected $_baseFields = [
        'discount_amount',
        'discount_canceled',
        'discount_invoiced',
        'discount_refunded',
        'grand_total',
        'shipping_amount',
        'shipping_canceled',
        'shipping_invoiced',
        'shipping_refunded',
        'shipping_tax_amount',
        'shipping_tax_refunded',
        'subtotal',
        'subtotal_canceled',
        'subtotal_invoiced',
        'subtotal_refunded',
        'tax_amount',
        'tax_canceled',
        'tax_invoiced',
        'tax_refunded',
        'total_canceled',
        'total_invoiced',
        'total_invoiced_cost',
        'total_offline_refunded',
        'total_online_refunded',
        'total_paid',
        'total_qty_ordered',
        'total_refunded',
        'adjustment_negative',
        'adjustment_positive',
        'shipping_discount_amount',
        'subtotal_incl_tax',
        'total_due',
        'discount_tax_compensation_amount',
        'discount_tax_compensation_invoiced',
        'discount_tax_compensation_refunded',
        'shipping_incl_tax',
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
        $this->prepareStatus($rowData);
        return (!$this->isEmptyRow($rowData))
            ? $rowData
            : false;
    }

    /**
     * Is Empty Row
     *
     * @param array $rowData
     * @return bool
     */
    public function isEmptyRow($rowData)
    {
        return empty($rowData['increment_id']) ||
            (!empty($rowData['increment_id']) &&
            (!empty($rowData['shipment_track:skus']) || !empty($rowData['creditmemo:skus'])));
    }

    /**
     * Retrieve Entity Id If Entity Is Present In Database
     *
     * @param array $rowData
     * @return bool|int
     */
    protected function _getExistEntityId(array $rowData)
    {
        $bind = [':increment_id' => $rowData[self::COLUMN_INCREMENT_ID]];
        /** @var $select \Magento\Framework\DB\Select */
        $select = $this->_connection->select();
        $select->from($this->getMainTable(), 'entity_id')
            ->where('increment_id = :increment_id');

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
        $entityRow = [];

        $newEntity = false;
        $entityId = $this->_getExistEntityId($rowData);
        if (!$entityId) {
            /* create new entity id */
            $newEntity = true;
            $entityId = $this->_getNextEntityId();
            $this->_newEntities[$rowData[self::COLUMN_INCREMENT_ID]] = $entityId;
        }

        $entityRow['entity_id'] = $entityId;
        $entityRow['store_id'] = $rowData[self::COLUMN_STORE_ID] ?? Store::DEFAULT_STORE_ID;
        $this->orderIdsMap[$rowData[self::COLUMN_INCREMENT_ID]] = $entityId;

        if (!empty($rowData['created_at']) || $newEntity) {
            list($createdAt, $updatedAt) = $this->_prepareDateTime($rowData);
            $entityRow['created_at'] = $createdAt;
            $entityRow['updated_at'] = $updatedAt;
        }

        if (!empty($rowData[self::COLUMN_CUSTOMER_EMAIL])) {
            $customerId = null;
            $customerGroupId = 0;
            $customerId = $this->getCustomerId(
                $rowData[self::COLUMN_CUSTOMER_EMAIL],
                $rowData[self::COLUMN_STORE_ID] ?? 0
            );

            if ($customerId) {
                $customerGroupId = $this->getCustomerGroupId($customerId);
            }

            $entityRow['customer_id'] = $customerId;
            $entityRow['customer_group_id'] = $customerGroupId;
            $entityRow['customer_is_guest'] = $customerId ? 0 : 1;
        }

        if ($newEntity && (!isset($rowData['base_to_order_rate']) || $rowData['base_to_order_rate'] == 1)) {
            foreach ($this->_baseFields as $field) {
                if (isset($rowData[$field]) && !isset($rowData['base_' . $field])) {
                    $rowData['base_' . $field] = $rowData[$field];
                } elseif (!isset($rowData[$field])) {
                    // set default values
                    $rowData[$field] = $rowData['base_' . $field] = 0;
                }
            }

            if (isset($rowData['shipping_discount_tax_compensation_amount']) &&
                !isset($rowData['base_shipping_discount_tax_compensation_amnt'])
            ) {
                $rowData['base_shipping_discount_tax_compensation_amnt'] =
                    $rowData['shipping_discount_tax_compensation_amount'];
            } else {
                $rowData['base_shipping_discount_tax_compensation_amnt'] =
                    $rowData['shipping_discount_tax_compensation_amount'] = 0;
            }

            foreach (['global_currency_code', 'base_currency_code', 'store_currency_code'] as $field) {
                if (!isset($rowData[$field]) && isset($rowData['order_currency_code'])) {
                    $rowData[$field] = $rowData['order_currency_code'];
                }
            }
        }

        if ($newEntity && empty($rowData['tax_amount'])) {
            if (!empty($rowData['subtotal'])) {
                $rowData['subtotal_incl_tax'] = $rowData['subtotal'];
            }
            if (!empty($rowData['shipping_amount'])) {
                $rowData['shipping_incl_tax'] = $rowData['shipping_amount'];
            }
            if (!empty($rowData['base_shipping_amount'])) {
                $rowData['base_shipping_incl_tax'] = $rowData['base_shipping_amount'];
            }
        }

        if (isset($rowData['is_virtual'])) {
            $entityRow['is_virtual'] = empty($rowData['is_virtual']) ? 0 : 1;
        }

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
     * Prepare Data For Replace
     *
     * @param array $rowData
     * @return array
     */
    protected function _prepareDataForReplace(array $rowData)
    {
        $toUpdate = [];
        $entityId = $this->_getExistEntityId($rowData);

        $this->orderIdsMap[$rowData[self::COLUMN_INCREMENT_ID]] = $entityId;
        $entityRow = [
            self::COLUMN_ENTITY_ID => $entityId
        ];
        /* prepare data */
        $toUpdate[] = $this->_prepareEntityRow($entityRow, $rowData);
        return [
            self::ENTITIES_TO_UPDATE_KEY => $toUpdate
        ];
    }

    /**
     * Retrieve Id For Delete
     *
     * @param array $rowData
     * @return string
     */
    protected function _getIdForDelete(array $rowData)
    {
        if (!empty($rowData[self::COLUMN_INCREMENT_ID])) {
            return $this->_getExistEntityId($rowData);
        }
        return parent::_getIdForDelete($rowData);
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
        if ($this->_checkIncrementIdKey($rowData, $rowNumber)) {
            $incrementId = $rowData[self::COLUMN_INCREMENT_ID];
            if (isset($this->_newEntities[$incrementId])) {
                $this->addRowError(self::ERROR_DUPLICATE_INCREMENT_ID, $rowNumber);
            }
        }
    }

    /**
     * Validate Row Data For Replace Behaviour
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return void
     */
    protected function _validateRowForReplace(array $rowData, $rowNumber)
    {
        $this->_checkDisjunctionKey($rowData, $rowNumber);
    }

    /**
     * Validate Row Data For Delete Behaviour
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return void
     */
    protected function _validateRowForDelete(array $rowData, $rowNumber)
    {
        $this->_checkDisjunctionKey($rowData, $rowNumber);
    }

    /**
     * Delete List Of Entities
     *
     * @param array $toDelete Entities Id List
     * @return $this
     */
    protected function _deleteEntities(array $toDelete)
    {
        parent::_deleteEntities($toDelete);
        foreach ([
            'sales_order_grid',
            'sales_shipment_grid',
            'sales_invoice_grid',
            'sales_creditmemo_grid'] as $table) {
            $column = ($table == 'sales_order_grid') ? self::COLUMN_ENTITY_ID : 'order_id';
            $condition = $this->_connection->quoteInto(
                $column . ' IN (?)',
                $toDelete
            );
            $this->_connection->delete(
                $this->_resource->getTableName($table),
                $condition
            );
        }
        return $this;
    }

    /**
     * Prepare Status
     *
     * @param array $rowData
     * @return void
     */
    public function prepareStatus(array $rowData)
    {
        if (empty($rowData['status']) || empty($rowData['status_label'])) {
            return;
        }
        if (null === $this->_status) {
            $this->_status = $this-> _getStatusCollection();
        }
        if (!in_array($rowData['status'], $this->_status)) {
            $this->saveStatus($rowData['status'], $rowData['status_label']);
        }
    }

    /**
     * Save Status
     *
     * @param string $status
     * @param string $label
     * @return void
     */
    public function saveStatus($status, $label)
    {
        $this->_status[] = $status;
        $this->_connection->insert(
            $this->getStatusTable(),
            ['status' => $status, 'label' => $label]
        );
    }

    /**
     * Retrieve Status Collection
     *
     * @return array
     */
    protected function _getStatusCollection()
    {
        /** @var $select \Magento\Framework\DB\Select */
        $select = $this->_connection->select();
        $select->from($this->getStatusTable(), 'status');

        return $this->_connection->fetchCol($select);
    }

    /**
     * Retrieve Status Table Name
     *
     * @return string
     */
    public function getStatusTable()
    {
        return $this->_resource->getTableName(
            $this->_statusTable
        );
    }
}
