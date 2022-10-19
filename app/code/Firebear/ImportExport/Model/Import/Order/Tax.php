<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Import\Order;

use Magento\ImportExport\Model\Import;

/**
 * Order Tax Import
 */
class Tax extends AbstractAdapter
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
    const PREFIX = 'tax';

    /**
     * Entity Id Column Name
     *
     */
    const COLUMN_ENTITY_ID = 'tax_id';

    /**
     * Code Column Name
     *
     */
    const COLUMN_CODE = 'code';

    /**
     * Percent Column Name
     *
     */
    const COLUMN_PERCENT = 'percent';

    /**
     * Error Codes
     */
    const ERROR_ENTITY_ID_IS_EMPTY = 'taxEntityIdIsEmpty';
    const ERROR_CODE_IS_EMPTY = 'taxCodeIsEmpty';
    const ERROR_PERCENT_IS_EMPTY = 'taxPercentIsEmpty';
    const ERROR_DUPLICATE_ENTITY_ID = 'duplicateTaxEntityId';

    /**
     * Validation Failure Message Template Definitions
     *
     * @var array
     */
    protected $_messageTemplates = [
        self::ERROR_DUPLICATE_ENTITY_ID => 'Tax tax_id is found more than once in the import file',
        self::ERROR_CODE_IS_EMPTY => 'Tax code is empty',
        self::ERROR_PERCENT_IS_EMPTY => 'Tax percent is empty',
        self::ERROR_ENTITY_ID_IS_EMPTY => 'Tax tax_id is empty'
    ];

    /**
     * Order Tax Table Name
     *
     * @var string
     */
    protected $_mainTable = 'sales_order_tax';

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
            ':order_id' => $this->_getOrderId($rowData),
            ':code' => $rowData[self::COLUMN_CODE],
            ':percent' => $rowData[self::COLUMN_PERCENT]
        ];
        /** @var $select \Magento\Framework\DB\Select */
        $select = $this->_connection->select();
        $select->from($this->getMainTable(), 'tax_id')
            ->where('order_id = :order_id')
            ->where('code = :code')
            ->where('percent = :percent');

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

        $this->taxIdsMap[$this->_getEntityId($rowData)] = $entityId;
        $entityRow = [
            'order_id' => $this->_getOrderId($rowData),
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
            if (empty($rowData[self::COLUMN_CODE])) {
                $this->addRowError(self::ERROR_CODE_IS_EMPTY, $rowNumber);
            }

            if (empty($rowData[self::COLUMN_PERCENT])) {
                $this->addRowError(self::ERROR_PERCENT_IS_EMPTY, $rowNumber);
            }
        }
    }
}
