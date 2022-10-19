<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Import\Order;

use Magento\ImportExport\Model\Import;

/**
 * Order Invoice Import
 */
class Invoice extends AbstractAdapter
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
    const PREFIX = 'invoice';

    /**
     * Entity Id Column Name
     *
     */
    const COLUMN_ENTITY_ID = 'entity_id';

    /**
     * Error Codes
     */
    const ERROR_ENTITY_ID_IS_EMPTY = 'invoiceEntityIdIsEmpty';
    const ERROR_DUPLICATE_ENTITY_ID = 'duplicateInvoiceEntityId';
    const ERROR_DUPLICATE_INCREMENT_ID = 'duplicateInvoiceIncrementId';
    const ERROR_INCREMENT_ID_IS_EMPTY = 'invoiceIncrementIdIsEmpty';

    /**
     * Validation Failure Message Template Definitions
     *
     * @var array
     */
    protected $_messageTemplates = [
        self::ERROR_DUPLICATE_ENTITY_ID => 'Invoice entity_id is found more than once in the import file',
        self::ERROR_DUPLICATE_INCREMENT_ID => 'Invoice increment_id is found more than once in the import file',
        self::ERROR_ENTITY_ID_IS_EMPTY => 'Invoice entity_id is empty',
        self::ERROR_INCREMENT_ID_IS_EMPTY => 'Invoice increment_id is empty',
    ];

    /**
     * Order Invoice Table Name
     *
     * @var string
     */
    protected $_mainTable = 'sales_invoice';

    /**
     *
     *
     * @var array
     */
    protected $_baseFields = [
        'grand_total',
        'tax_amount',
        'shipping_tax_amount',
        'discount_amount',
        'subtotal_incl_tax',
        'shipping_amount',
        'subtotal',
        'discount_tax_compensation_amount',
    ];

    /**
     *
     *
     * @var array
     */
    protected $_taxFields = [
        'base_subtotal',
        'subtotal',
        'shipping',
        'base_shipping',
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
        $bind = [':increment_id' => $rowData[self::COLUMN_INCREMENT_ID]];
        /** @var $select \Magento\Framework\DB\Select */
        $select = $this->_connection->select();
        $select->from($this->getMainTable(), 'entity_id')
            ->where('increment_id = :increment_id');

        return $this->_connection->fetchOne($select, $bind);
    }

    /**
     * Retrieve Shipping Address Id
     *
     * @param array $rowData
     * @return bool|int
     */
    protected function _getShippingAddressId($rowData)
    {
        $bind = [
            ':address_type' => 'shipping',
            ':parent_id' => $this->_getOrderId($rowData)
        ];
        /** @var $select \Magento\Framework\DB\Select */
        $select = $this->_connection->select();
        $select->from($this->getAddressTable(), 'entity_id')
            ->where('parent_id = :parent_id')
            ->where('address_type = :address_type');

        return $this->_connection->fetchOne($select, $bind);
    }

    /**
     * Retrieve Billing Address Id
     *
     * @param array $rowData
     * @return bool|int
     */
    protected function _getBillingAddressId($rowData)
    {
        $bind = [
            ':address_type' => 'billing',
            ':parent_id' => $this->_getOrderId($rowData)
        ];
        /** @var $select \Magento\Framework\DB\Select */
        $select = $this->_connection->select();
        $select->from($this->getAddressTable(), 'entity_id')
            ->where('parent_id = :parent_id')
            ->where('address_type = :address_type');

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

        $newEntity = false;
        $entityId = $this->_getExistEntityId($rowData);
        if (!$entityId) {
            /* create new entity id */
            $newEntity = true;
            $entityId = $this->_getNextEntityId();
            $this->_newEntities[$rowData[self::COLUMN_INCREMENT_ID]] = $entityId;
        }

        $this->invoiceIdsMap[$this->_getEntityId($rowData)] = $entityId;

        if (!isset($rowData['base_to_order_rate']) || $rowData['base_to_order_rate'] == 1) {
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

        if (empty($rowData['tax_amount'])) {
            foreach ($this->_taxFields as $field) {
                if (!empty($rowData[$field])) {
                    $rowData[$field . '_incl_tax'] = $rowData[$field];
                }
            }
        }

        $entityRow = [
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
            'order_id' => $this->_getOrderId($rowData),
            'shipping_address_id' => $this->_getShippingAddressId($rowData),
            'billing_address_id' => $this->_getBillingAddressId($rowData),
            'entity_id' => $entityId
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
        if ($this->_checkIncrementIdKey($rowData, $rowNumber)) {
            $incrementId = $rowData[self::COLUMN_INCREMENT_ID];
            if (isset($this->_newEntities[$incrementId])) {
                $this->addRowError(self::ERROR_DUPLICATE_INCREMENT_ID, $rowNumber);
            }
        }
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
        $this->_checkIncrementIdKey($rowData, $rowNumber);
    }
}
