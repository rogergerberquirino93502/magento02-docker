<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Order;

use Firebear\ImportExport\Model\Import\Context;
use Firebear\ImportExport\Model\ResourceModel\Order\Helper;
use Magento\Framework\DB\Select;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Serialize\Serializer\Serialize;

/**
 * Class Order Payment Import
 *
 * @package Firebear\ImportExport\Model\Import\Order
 */
class Payment extends AbstractAdapter
{
    /**
     * Entity Type Code
     */
    const ENTITY_TYPE_CODE = 'order';

    /**
     * Prefix of Fields
     *
     */
    const PREFIX = 'payment';

    /**
     * Entity Id Column Name
     */
    const COLUMN_ENTITY_ID = 'entity_id';

    /**
     * Error Codes
     */
    const ERROR_ENTITY_ID_IS_EMPTY = 'paymentEntityIdIsEmpty';
    const ERROR_DUPLICATE_ENTITY_ID = 'duplicatePaymentEntityId';

    /**
     * Validation Failure Message Template Definitions
     *
     * @var array
     */
    protected $_messageTemplates = [
        self::ERROR_DUPLICATE_ENTITY_ID => 'Payment entity_id is found more than once in the import file',
        self::ERROR_ENTITY_ID_IS_EMPTY => 'Payment entity_id is empty'
    ];

    /**
     * Order Payment Table Name
     *
     * @var string
     */
    protected $_mainTable = 'sales_order_payment';

    /**
     * @var Serialize
     */
    private $serialize;

    /**
     * @var SerializerInterface
     */
    protected $jsonSerializer;

    /**
     * Payment constructor.
     * @param Context $context
     * @param Helper $resourceHelper
     * @param Serialize $serialize
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        Context $context,
        Helper $resourceHelper,
        Serialize $serialize
    ) {
        parent::__construct($context, $resourceHelper);
        $this->jsonSerializer = $context->getSerializer();
        $this->serialize = $serialize;
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
            if ($this->isSerialized($rowData['additional_information'])) {
                $rowData['additional_information'] = $this->jsonSerializer
                    ->serialize($this->serialize->unserialize($rowData['additional_information']));
            }
        }
        return (count($rowData) && !$this->isEmptyRow($rowData))
            ? $rowData
            : false;
    }

    /**
     * @param mixed $data
     * @param bool $strict
     * @return bool
     */
    private function isSerialized($data, $strict = true)
    {
        // if it isn't a string, it isn't serialized.
        if (!is_string($data)) {
            return false;
        }
        $data = trim($data);
        if ('N;' == $data) {
            return true;
        }
        if (strlen($data) < 4) {
            return false;
        }
        if (':' !== $data[1]) {
            return false;
        }
        if ($strict) {
            $lastc = substr($data, -1);
            if (';' !== $lastc && '}' !== $lastc) {
                return false;
            }
        } else {
            $semicolon = strpos($data, ';');
            $brace = strpos($data, '}');
            // Either ; or } must exist.
            if (false === $semicolon && false === $brace) {
                return false;
            }
            // But neither must be in the first X characters.
            if (false !== $semicolon && $semicolon < 3) {
                return false;
            }
            if (false !== $brace && $brace < 4) {
                return false;
            }
        }
        $token = $data[0];
        switch ($token) {
            case 's':
                if ($strict) {
                    if ('"' !== substr($data, -2, 1)) {
                        return false;
                    }
                } elseif (false === strpos($data, '"')) {
                    return false;
                }
            // or else fall through
            case 'a':
            case 'O':
                return (bool)preg_match("/^{$token}:[0-9]+:/s", $data);
            case 'b':
            case 'i':
            case 'd':
                $end = $strict ? '$' : '';
                return (bool)preg_match("/^{$token}:[0-9.E-]+;$end/", $data);
        }
        return false;
    }

    /**
     * Retrieve Entity Id If Entity Is Present In Database
     *
     * @param array $rowData
     * @return bool|int
     */
    protected function _getExistEntityId(array $rowData)
    {
        $bind = [':parent_id' => $this->_getOrderId($rowData)];
        /** @var $select Select */
        $select = $this->_connection->select();
        $select->from($this->getMainTable(), 'entity_id')
            ->where('parent_id = :parent_id');

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
        $orderId = $this->_getOrderId($rowData);
        if (!$entityId) {
            /* create new entity id */
            $newEntity = true;
            $entityId = $this->_getNextEntityId();
            $this->_newEntities[$rowData[self::COLUMN_ENTITY_ID]] = $entityId;
        }

        $this->paymentIdsMap[$this->_getEntityId($rowData)] = $entityId;

        $entityRow = [
            'parent_id' => $orderId,
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
        $this->_checkEntityIdKey($rowData, $rowNumber);
    }
}
