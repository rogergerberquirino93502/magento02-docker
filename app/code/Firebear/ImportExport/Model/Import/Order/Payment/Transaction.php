<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Import\Order\Payment;

use Magento\ImportExport\Model\Import;
use Firebear\ImportExport\Model\Import\Order\AbstractAdapter;

/**
 * Order Payment Transaction Import
 */
class Transaction extends AbstractAdapter
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
    const PREFIX = 'transaction';

    /**
     * Entity Id Column Name
     *
     */
    const COLUMN_ENTITY_ID = 'transaction_id';

    /**
     * Payment Id Column Name
     *
     */
    const COLUMN_PAYMENT_ID = 'payment_id';

    /**
     * Transaction Parent Id Column Name
     *
     */
    const COLUMN_PARENT_ID = 'parent_id';

    /**
     * Order Id Column Name
     *
     */
    const COLUMN_ORDER_ID = 'order_id';

    /**
     * Error Codes
     */
    const ERROR_ENTITY_ID_IS_EMPTY = 'paymentTransactionEntityIdIsEmpty';
    const ERROR_PAYMENT_ID_IS_EMPTY = 'paymentTransactionPaymentIdIsEmpty';
    const ERROR_DUPLICATE_ENTITY_ID = 'duplicatePaymentTransactionId';

    /**
     * Validation Failure Message Template Definitions
     *
     * @var array
     */
    protected $_messageTemplates = [
        self::ERROR_DUPLICATE_ENTITY_ID =>
            'Payment Transaction transaction_id is found more than once in the import file',
        self::ERROR_ENTITY_ID_IS_EMPTY => 'Payment Transaction transaction_id is empty',
        self::ERROR_PAYMENT_ID_IS_EMPTY => 'Payment Transaction payment_id is empty',
    ];

    /**
     * Transaction Ids Map
     *
     * @var array
     */
    protected $transactionIdsMap;

    /**
     * Order Payment Transaction Table Name
     *
     * @var string
     */
    protected $_mainTable = 'sales_payment_transaction';

    /**
     * Retrieve Transaction Ids Map
     *
     * @return array
     */
    public function getTransactionIdsMap()
    {
        return $this->transactionIdsMap ?: [];
    }

    /**
     * Set Transaction Ids Map
     *
     * @param array $transactionIds
     * @return $this
     */
    public function setTransactionIdsMap(array $transactionIds)
    {
        $this->transactionIdsMap = $transactionIds;

        return $this;
    }

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
        if (!empty($rowData['additional_information'])) {
            $isJson = json_decode($rowData['additional_information']);
            if (json_last_error() != JSON_ERROR_NONE) {
                $rowData['additional_information'] = base64_decode($rowData['additional_information']);
            }
        }
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
            ':order_id' => $this->_getOrderId($rowData),
            ':payment_id' => $this->_getPaymentId($rowData),
            ':txn_id' => $rowData['txn_id']
        ];
        /** @var $select \Magento\Framework\DB\Select */
        $select = $this->_connection->select();
        $select->from($this->getMainTable(), 'transaction_id')
            ->where('payment_id = :payment_id')
            ->where('order_id = :order_id')
            ->where('txn_id = :txn_id');

        return $this->_connection->fetchOne($select, $bind);
    }

    /**
     * Retrieve Transaction Parent Id If Parent Is Present In Database
     *
     * @param array $rowData
     * @return bool|int
     */
    protected function _getParentId(array $rowData)
    {
        if (null !== $this->transactionIdsMap) {
            $parentId = $rowData[self::COLUMN_PARENT_ID];
            if (isset($this->transactionIdsMap[$parentId])) {
                return $this->transactionIdsMap[$parentId];
            }
        }
        return false;
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
            $this->_newEntities[$rowData[self::COLUMN_ENTITY_ID]] = $entityId;
        }

        $this->transactionIdsMap[$this->_getEntityId($rowData)] = $entityId;

        $entityRow = [
            self::COLUMN_ENTITY_ID => $entityId,
            self::COLUMN_PAYMENT_ID => $this->_getPaymentId($rowData),
            self::COLUMN_ORDER_ID => $this->_getOrderId($rowData),
            self::COLUMN_PARENT_ID => empty($rowData[self::COLUMN_PARENT_ID]) ? null : $this->_getParentId($rowData),
            'created_at' => $createdAt
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
            if (empty($rowData[self::COLUMN_PAYMENT_ID])) {
                $this->addRowError(self::ERROR_PAYMENT_ID_IS_EMPTY, $rowNumber);
            }
        }
    }
}
