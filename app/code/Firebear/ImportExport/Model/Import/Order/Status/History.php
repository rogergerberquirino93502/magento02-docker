<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Import\Order\Status;

use Magento\ImportExport\Model\Import;
use Firebear\ImportExport\Model\Import\Order\AbstractAdapter;

/**
 * Order Status History Import
 */
class History extends AbstractAdapter
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
    const PREFIX = 'status_history';

    /**
     * Entity Id Column Name
     *
     */
    const COLUMN_ENTITY_ID = 'entity_id';

    /**
     * Order Id Column Name
     *
     */
    const COLUMN_ORDER_ID = 'parent_id';

    /**
     * Error Codes
     */
    const ERROR_ENTITY_ID_IS_EMPTY = 'statusHistoryEntityIdIsEmpty';
    const ERROR_ORDER_ID_IS_EMPTY = 'statusHistoryOrderIdIsEmpty';
    const ERROR_DUPLICATE_ENTITY_ID = 'duplicateStatusHistoryEntityId';

    /**
     * Validation Failure Message Template Definitions
     *
     * @var array
     */
    protected $_messageTemplates = [
        self::ERROR_DUPLICATE_ENTITY_ID => 'Status History entity_id is found more than once in the import file',
        self::ERROR_ORDER_ID_IS_EMPTY => 'Status History parent_id is empty',
        self::ERROR_ENTITY_ID_IS_EMPTY => 'Status History entity_id is empty'
    ];

    /**
     * Order Status History Table Name
     *
     * @var string
     */
    protected $_mainTable = 'sales_order_status_history';

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
            ':parent_id' => $rowData[self::COLUMN_ORDER_ID],
            ':comment' => $rowData['comment'],
            ':entity_name' => $rowData['entity_name']
        ];
        /** @var $select \Magento\Framework\DB\Select */
        $select = $this->_connection->select();
        $select->from($this->getMainTable(), 'entity_id')
            ->where('parent_id = :parent_id')
            ->where('comment = :comment')
            ->where('entity_name = :entity_name');

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
            $this->_newEntities[$rowData[self::COLUMN_ENTITY_ID]] = $entityId;
        }

        $entityRow = [
            'created_at' => $createdAt,
            self::COLUMN_ORDER_ID => $this->_getOrderId($rowData),
            self::COLUMN_ENTITY_ID => $entityId
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
            if (empty($rowData[self::COLUMN_ORDER_ID])) {
                $this->addRowError(self::ERROR_ORDER_ID_IS_EMPTY, $rowNumber);
            }
        }
    }
}
