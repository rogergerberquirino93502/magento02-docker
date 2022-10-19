<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Import\Order\Shipment;

use Firebear\ImportExport\Model\ResourceModel\Order\Helper;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\ShipmentFactory;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as ShipmentCollectionFactory;
use Magento\Shipping\Model\ShipmentNotifierFactory;
use Firebear\ImportExport\Model\Import\Order\AbstractAdapter;
use Firebear\ImportExport\Model\Import\Context;

/**
 * Order Track Import
 */
class Track extends AbstractAdapter
{
    /**
     * Entity Type Code
     */
    const ENTITY_TYPE_CODE = 'order';

    /**
     * Prefix of Fields
     *
     */
    const PREFIX = 'shipment_track';

    /**
     * Entity Id Column Name
     */
    const COLUMN_ENTITY_ID = 'entity_id';

    /**
     * Shipment Id Column Name
     */
    const COLUMN_SHIPMENT_ID = 'parent_id';

    /**
     * Shipment Increment Id Column Name
     */
    const COLUMN_SHIPMENT_INCREMENT_ID = 'shipment_increment_id';

    /**
     * Order Id Column Name
     */
    const COLUMN_ORDER_ID = 'order_id';

    /**
     * Track Number Column Name
     */
    const COLUMN_TRACK_NUMBER = 'track_number';

    /**
     * Error Codes
     */
    const ERROR_ENTITY_ID_IS_EMPTY = 'shipmentTrackIdIsEmpty';
    const ERROR_SHIPMENT_ID_IS_EMPTY = 'shipmentTrackParentIdIsEmpty';
    const ERROR_DUPLICATE_ENTITY_ID = 'duplicateShipmentTrackId';
    const ERROR_ORDER_ID_IS_EMPTY = 'shipmentTrackOrderIdIsEmpty';
    const ERROR_TRACK_NUMBER_IS_EMPTY = 'shipmentTrackNumberIsEmpty';
    const ERROR_SHIPMENT_INCREMENT_ID = 'shipmentTrackIncrementId';
    const ERROR_ORDER_INCREMENT_ID = 'orderTrackIncrementId';
    const ERROR_SHIPMENT_COUNT = 'shipmentItemCount';
    const ERROR_SHIPMENT_IS_EMPTY = 'shipmentItemIsEmpty';
    const ERROR_SKUS_INCORRECT = 'skusIncorrect';
    const ERROR_QTY_INCORRECT = 'qtyIncorrect';
    const ERROR_SKU_NOT_FOUND = 'skuNotFound';

    /**
     * Validation Failure Message Template Definitions
     *
     * @var array
     */
    protected $_messageTemplates = [
        self::ERROR_DUPLICATE_ENTITY_ID => 'Shipment Track entity_id is found more than once in the import file',
        self::ERROR_ENTITY_ID_IS_EMPTY => 'Shipment Track entity_id is empty',
        self::ERROR_SHIPMENT_ID_IS_EMPTY => 'Shipment Track parent_id is empty',
        self::ERROR_ORDER_ID_IS_EMPTY => 'Shipment Track order_id is empty',
        self::ERROR_TRACK_NUMBER_IS_EMPTY => 'Shipment Track track_number is empty',
        self::ERROR_SHIPMENT_INCREMENT_ID => 'Shipment with selected shipment:increment_id does not exist',
        self::ERROR_ORDER_INCREMENT_ID => 'Order with selected increment_id does not exist',
        self::ERROR_SHIPMENT_COUNT => 'Order has more than 1 Shipment. please specify Shipment ID.',
        self::ERROR_SHIPMENT_IS_EMPTY => 'Order does not have Shipment.',
        self::ERROR_SKUS_INCORRECT => 'Skus string is incorrect.',
        self::ERROR_QTY_INCORRECT => 'Product with sku %s has incorrect qty.',
        self::ERROR_SKU_NOT_FOUND => 'Product with sku %s does not exist.',
    ];

    /**
     * Order Shipment Track Table Name
     *
     * @var string
     */
    protected $_mainTable = 'sales_shipment_track';

    /**
     * Shipment Collection
     *
     * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Collection;
     */
    protected $shipmentCollection;

    /**
     * Shipment Collection Factory
     *
     * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory;
     */
    protected $shipmentCollectionFactory;

    /**
     * Shipment Notifier
     *
     * @var \Magento\Shipping\Model\ShipmentNotifier;
     */
    protected $notifier;

    /**
     * Shipment Notifier Factory
     *
     * @var \Magento\Shipping\Model\ShipmentNotifierFactory;
     */
    protected $notifierFactory;

    /**
     * Shipment Factory
     *
     * @var ShipmentFactory
     */
    protected $shipmentFactory;

    /**
     * Order Repository
     *
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * Transaction Factory
     *
     * @var TransactionFactory
     */
    protected $transactionFactory;

    /**
     * Track constructor
     *
     * @param Context $context
     * @param Helper $resourceHelper
     * @param ShipmentCollectionFactory $shipmentCollectionFactory
     * @param ShipmentNotifierFactory $notifierFactory
     * @param ShipmentFactory $shipmentFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param TransactionFactory $transactionFactory
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        Context $context,
        Helper $resourceHelper,
        ShipmentCollectionFactory $shipmentCollectionFactory,
        ShipmentNotifierFactory $notifierFactory,
        ShipmentFactory $shipmentFactory,
        OrderRepositoryInterface $orderRepository,
        TransactionFactory $transactionFactory
    ) {
        $this->shipmentCollectionFactory = $shipmentCollectionFactory;
        $this->notifierFactory = $notifierFactory;
        $this->shipmentFactory = $shipmentFactory;
        $this->orderRepository = $orderRepository;
        $this->transactionFactory = $transactionFactory;

        parent::__construct(
            $context,
            $resourceHelper
        );
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
            ':parent_id' => $this->_getShipmentId($rowData),
            ':track_number' => $rowData['track_number']
        ];
        /** @var $select \Magento\Framework\DB\Select */
        $select = $this->_connection->select();
        $select->from($this->getMainTable(), 'entity_id')
            ->where('parent_id = :parent_id')
            ->where('order_id = :order_id')
            ->where('track_number = :track_number');

        return $this->_connection->fetchOne($select, $bind);
    }

    /**
     * Retrieve item skus
     *
     * @param string $skus
     * @return array
     */
    protected function getSkus($skus)
    {
        $data = [];
        foreach (explode(';', trim($skus, ';')) as $row) {
            list($sku, $qty) = explode(':', $row);
            $data[$sku] = $qty;
        }
        return $data;
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
        /* auto generate shipment and order ids */
        if (!empty($rowData[self::COLUMN_SHIPMENT_INCREMENT_ID])) {
            $shipmentId = $this->_getExistShipmentId($rowData);
            if (empty($this->shipmentIdsMap[$shipmentId])) {
                $this->shipmentIdsMap[$shipmentId] = $shipmentId;
            }
            $rowData[self::COLUMN_SHIPMENT_ID] = $shipmentId;

            if (empty($rowData[self::COLUMN_ORDER_ID])) {
                $orderId = $this->_getOrderIdByShipment($rowData);
                if (empty($this->orderIdsMap[$orderId])) {
                    $this->orderIdsMap[$orderId] = $orderId;
                }
                $rowData[self::COLUMN_ORDER_ID] = $orderId;
            }

            if (empty($this->shipmentIdsMap[$shipmentId])) {
                $this->shipmentIdsMap[$shipmentId] = $shipmentId;
            }
            $rowData[self::COLUMN_SHIPMENT_ID] = $shipmentId;
        }

        if (!empty($this->_currentOrderId)) {
            $itemsToShip = [];
            $itemsToInvoice = [];
            $order = null;
            /* create order */
            if ((!empty($this->_parameters['generate_shipment_by_track']) ||
                !empty($this->_parameters['generate_invoice_by_track']))
            ) {
                $order = $this->orderRepository->get(
                    $this->_getExistOrderId()
                );

                /* check if the column of the skus is empty or not */
                if (!empty($rowData['skus'])) {
                    $data = $this->getSkus($rowData['skus']);
                }
                foreach ($order->getAllVisibleItems() as $item) {

                    if (!isset($data[$item->getSku()]) && !empty($rowData['skus'])) {
                        continue;
                    }

                    if ($item->canShip()) {
                        /* if sku columns not empty, it takes the minimum qty */
                        if (!empty($rowData['skus'])) {
                            $qty = min($data[$item->getSku()], $item->getQtyToShip());
                        } else {
                            $qty = $item->getQtyToShip();
                        }
                        if (0 < $qty) {
                            $itemsToShip[$item->getId()] = $qty;
                        }
                    }

                    if ($item->canInvoice()) {
                        /* if sku columns not empty, it takes the minimum qty */
                        if (!empty($rowData['skus'])) {
                            $qty = min($data[$item->getSku()], $item->getQtyToInvoice());
                        } else {
                            $qty = $item->getQtyToInvoice();
                        }
                        if (0 < $qty) {
                            $itemsToInvoice[$item->getId()] = $qty;
                        }
                    }
                }
            }

            /* create shipment */
            if (!empty($this->_parameters['generate_shipment_by_track']) &&
                (empty($rowData[self::COLUMN_SHIPMENT_INCREMENT_ID]) && empty($rowData[self::COLUMN_SHIPMENT_ID])) &&
                0 < count($itemsToShip)
            ) {
                $shipment = $this->shipmentFactory->create($order, $itemsToShip);
                if ($shipment->getTotalQty()) {
                    $shipment->register();

                    $transaction = $this->transactionFactory->create();
                    $transaction->addObject(
                        $shipment
                    )->addObject(
                        $order
                    )->save();

                    $this->addLogWriteln(
                        __('generate shipment with id %1', $shipment->getIncrementId()),
                        $this->output,
                        'info'
                    );

                    $shipmentId = $shipment->getId();
                    if (empty($this->shipmentIdsMap[$shipmentId])) {
                        $this->shipmentIdsMap[$shipmentId] = $shipmentId;
                    }
                    $rowData[self::COLUMN_SHIPMENT_ID] = $shipmentId;
                }
            }

            /* create invoice */
            if (!empty($this->_parameters['generate_invoice_by_track']) &&
                0 < count($itemsToInvoice)
            ) {
                $invoice = $order->prepareInvoice($itemsToInvoice);
                if ($invoice->getTotalQty()) {
                    $invoice->register();

                    $transaction = $this->transactionFactory->create();
                    $transaction->addObject(
                        $invoice
                    )->addObject(
                        $order
                    )->save();

                    $this->addLogWriteln(
                        __('generate invoice with id %1', $invoice->getIncrementId()),
                        $this->output,
                        'info'
                    );
                }
            }

            if (count($itemsToShip) > 0 && count($itemsToInvoice) > 0) {
                //change order status
                if (isset($rowData['status']) && !empty($rowData['status'])) {
                    $order->setData('status', $rowData['status']);
                    $order->setData('state', $rowData['status']);
                    $order->save();
                }
            }
        }

        if (empty($rowData[self::COLUMN_SHIPMENT_ID])) {
            return [
                self::ENTITIES_TO_CREATE_KEY => [],
                self::ENTITIES_TO_UPDATE_KEY => []
            ];
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
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
            'entity_id' => $entityId,
            'parent_id' => $this->_getShipmentId($rowData),
            'order_id' => $this->_getExistOrderId()
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
        if (!empty($rowData[self::COLUMN_SHIPMENT_INCREMENT_ID])) {
            /* check there is real shipment */
            if (!$this->_getExistShipmentId($rowData)) {
                $this->addRowError(self::ERROR_SHIPMENT_INCREMENT_ID, $rowNumber);
            }
        } elseif ($this->isShortFormat($rowData)) {
            /* check there is real order */
            if (!empty($rowData['skus']) && !$this->_getExistOrderId()) {
                $this->addRowError(self::ERROR_ORDER_INCREMENT_ID, $rowNumber);
            }

            $shipmentCount = $this->_getShipmentCount();
            if ((empty($rowData[self::COLUMN_SHIPMENT_INCREMENT_ID]) && empty($rowData[self::COLUMN_SHIPMENT_ID])) &&
                1 < $shipmentCount) {
                $this->addRowError(self::ERROR_SHIPMENT_COUNT, $rowNumber);
            }
        } elseif ($this->_checkEntityIdKey($rowData, $rowNumber)) {
            if (empty($rowData[self::COLUMN_SHIPMENT_ID])) {
                $this->addRowError(self::ERROR_SHIPMENT_ID_IS_EMPTY, $rowNumber);
            }

            if (empty($rowData[self::COLUMN_ORDER_ID])) {
                $this->addRowError(self::ERROR_ORDER_ID_IS_EMPTY, $rowNumber);
            }
        }

        if (empty($rowData[self::COLUMN_TRACK_NUMBER])) {
            $this->addRowError(self::ERROR_TRACK_NUMBER_IS_EMPTY, $rowNumber);
        }

        if (!empty($rowData['skus'])) {
            if (false === strpos($rowData['skus'], ':')) {
                $this->addRowError(self::ERROR_SKUS_INCORRECT, $rowNumber);
            } else {
                $data = $this->getSkus($rowData['skus']);
                foreach ($data as $sku => $qty) {
                    if (!is_numeric($qty) || 1 > $qty) {
                        $this->addRowError(self::ERROR_QTY_INCORRECT, $rowNumber, $sku);
                    }
                    if (!$this->getProductIdBySku($sku)) {
                        $this->addRowError(self::ERROR_SKU_NOT_FOUND, $rowNumber, $sku);
                    }
                }
            }
        }
    }

    /**
     * check if data is short format
     *
     * @param array $rowData
     * @return bool
     */
    protected function isShortFormat(array $rowData)
    {
        if (empty($this->_currentOrderId)) {
            return false;
        }
        return !empty($rowData['skus']) || !empty($rowData['track_number']);
    }

    /**
     * Retrieve Shipment Id If Shipment Is Present In Database
     *
     * @param array $rowData
     * @return bool|int
     */
    protected function _getExistShipmentId(array $rowData)
    {
        $bind = [':increment_id' => $rowData[self::COLUMN_SHIPMENT_INCREMENT_ID]];
        /** @var $select \Magento\Framework\DB\Select */
        $select = $this->_connection->select();
        $select->from($this->getShipmentTable(), 'entity_id')
            ->where('increment_id = :increment_id');

        return $this->_connection->fetchOne($select, $bind);
    }

    /**
     * Retrieve Order Id If Order Is Present In Database
     *
     * @return bool|int
     */
    protected function _getExistOrderId()
    {
        /** @var $select \Magento\Framework\DB\Select */
        $select = $this->_connection->select();
        $select->from($this->getOrderTable(), 'entity_id')
            ->where('increment_id = ?', $this->_currentOrderId);

        return $this->_connection->fetchOne($select);
    }

    /**
     * Retrieve Order Id If Shipment Is Present In Database
     *
     * @param array $rowData
     * @return bool|int
     */
    protected function _getOrderIdByShipment(array $rowData)
    {
        $bind = [':increment_id' => $rowData[self::COLUMN_SHIPMENT_INCREMENT_ID]];
        /** @var $select \Magento\Framework\DB\Select */
        $select = $this->_connection->select();
        $select->from($this->getShipmentTable(), 'order_id')
            ->where('increment_id = :increment_id');

        return $this->_connection->fetchOne($select, $bind);
    }

    /**
     * Retrieve count Shipment of order
     *
     * @return bool|int
     */
    protected function _getShipmentCount()
    {
        /** @var $select \Magento\Framework\DB\Select */
        $select = $this->_connection->select();
        $select->from(['s' => $this->getShipmentTable()], 'COUNT(*)')
            ->join(
                ['o' => $this->getOrderTable()],
                'o.entity_id = s.order_id',
                []
            )->where('o.increment_id = ?', $this->_currentOrderId);

        return $this->_connection->fetchOne($select);
    }

    /**
     * Retrieve Shipment Id by order
     *
     * @return bool|int
     */
    protected function _getShipmentIdByOrder()
    {
        /** @var $select \Magento\Framework\DB\Select */
        $select = $this->_connection->select();
        $select->from(['s' => $this->getShipmentTable()], 's.entity_id')
            ->join(
                ['o' => $this->getOrderTable()],
                'o.entity_id = s.order_id',
                []
            )->where('o.increment_id = ?', $this->_currentOrderId);

        return $this->_connection->fetchOne($select);
    }

    /**
     * Update And Insert Data In Entity Table
     *
     * @param array $toCreate Rows for insert
     * @param array $toUpdate Rows for update
     * @return $this
     */
    protected function _saveEntities(array $toCreate, array $toUpdate)
    {
        parent::_saveEntities($toCreate, $toUpdate);
        if ($this->_parameters['send_email']) {
            $this->_sendEmail(array_column($toCreate, 'parent_id'));
        }
        return $this;
    }

    /**
     * Send emails
     *
     * @param array $shipmentIds
     * @return $this
     */
    protected function _sendEmail(array $shipmentIds)
    {
        $this->addLogWriteln(__('Sending emails.'), $this->output, 'info');
        $collection = $this->getShipmentCollection()
            ->addFieldToFilter('entity_id', ['in' => array_unique($shipmentIds)]);

        try {
            /** @var \Magento\Sales\Model\Order\Shipment $shipment */
            foreach ($collection as $shipment) {
                $shipment->setEmailSent(true);
                $this->getNotifier()->notify($shipment);
            }
        } catch (\Exception $e) {
            $this->addLogWriteln(__('An error occurred while sending emails.'), $this->output, 'error');
            $this->_logger->critical($e);
        }
        return $this;
    }

    /**
     * Retrieve shipment collection
     *
     * @return \Magento\Sales\Model\ResourceModel\Order\Shipment\Collection
     */
    public function getShipmentCollection()
    {
        if (!$this->shipmentCollection) {
            $this->shipmentCollection = $this->shipmentCollectionFactory->create();
        }
        return $this->shipmentCollection;
    }

    /**
     * Retrieve shipment notifier
     *
     * @return \Magento\Shipping\Model\ShipmentNotifier
     */
    public function getNotifier()
    {
        if (!$this->notifier) {
            $this->notifier = $this->notifierFactory->create();
        }
        return $this->notifier;
    }
}
