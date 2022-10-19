<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import;

use Exception;
use Firebear\ImportExport\Helper\Data as Helper;
use Firebear\ImportExport\Model\Import\Order\AddressFactory;
use Firebear\ImportExport\Model\Import\Order\Creditmemo;
use Firebear\ImportExport\Model\Import\Order\Creditmemo\Comment;
use Firebear\ImportExport\Model\Import\Order\Creditmemo\CommentFactory as CreditmemoCommentFactory;
use Firebear\ImportExport\Model\Import\Order\Creditmemo\ItemFactory as CreditmemoItemFactory;
use Firebear\ImportExport\Model\Import\Order\CreditmemoFactory;
use Firebear\ImportExport\Model\Import\Order\DataProcessor;
use Firebear\ImportExport\Model\Import\Order\Entity;
use Firebear\ImportExport\Model\Import\Order\EntityFactory;
use Firebear\ImportExport\Model\Import\Order\Invoice;
use Firebear\ImportExport\Model\Import\Order\Invoice\CommentFactory as InvoiceCommentFactory;
use Firebear\ImportExport\Model\Import\Order\Invoice\ItemFactory as InvoiceItemFactory;
use Firebear\ImportExport\Model\Import\Order\InvoiceFactory;
use Firebear\ImportExport\Model\Import\Order\ItemFactory;
use Firebear\ImportExport\Model\Import\Order\Payment;
use Firebear\ImportExport\Model\Import\Order\Payment\Transaction;
use Firebear\ImportExport\Model\Import\Order\Payment\TransactionFactory;
use Firebear\ImportExport\Model\Import\Order\PaymentFactory;
use Firebear\ImportExport\Model\Import\Order\Shipment;
use Firebear\ImportExport\Model\Import\Order\Shipment\CommentFactory as ShipmentCommentFactory;
use Firebear\ImportExport\Model\Import\Order\Shipment\ItemFactory as ShipmentItemFactory;
use Firebear\ImportExport\Model\Import\Order\Shipment\Track;
use Firebear\ImportExport\Model\Import\Order\Shipment\TrackFactory as ShipmentTrackFactory;
use Firebear\ImportExport\Model\Import\Order\ShipmentFactory;
use Firebear\ImportExport\Model\Import\Order\Status\HistoryFactory as StatusHistoryFactory;
use Firebear\ImportExport\Model\Import\Order\Tax;
use Firebear\ImportExport\Model\Import\Order\Tax\Item;
use Firebear\ImportExport\Model\Import\Order\Tax\ItemFactory as TaxItemFactory;
use Firebear\ImportExport\Model\Import\Order\TaxFactory;
use Firebear\ImportExport\Traits\Import\Entity as ImportTrait;
use InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\ImportExport\Model\Import\AbstractSource;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\OfflinePayments\Model\Checkmo;
use Magento\Sales\Model\ResourceModel\GridPool;

/**
 * Order Import
 */
class Order extends AbstractEntity implements ImportAdapterInterface
{
    use ImportTrait;

    /**
     * Entity Type Code
     *
     */
    const ENTITY_TYPE_CODE = 'order';

    /**
     * Order Entity Adapter
     *
     * @var Entity
     */
    protected $_order;

    /**
     * Item Entity Adapter
     *
     * @var \Firebear\ImportExport\Model\Import\Order\Item
     */
    protected $_item;

    /**
     * Address Entity Adapter
     *
     * @var \Firebear\ImportExport\Model\Import\Order\Address
     */
    protected $_address;

    /**
     * Shipment Entity Adapter
     *
     * @var Shipment
     */
    protected $_shipment;

    /**
     * Shipment Item Entity Adapter
     *
     * @var \Firebear\ImportExport\Model\Import\Order\Shipment\Item
     */
    protected $_shipmentItem;

    /**
     * Shipment Track Entity Adapter
     *
     * @var Track
     */
    protected $_shipmentTrack;

    /**
     * Shipment Comment Entity Adapter
     *
     * @var \Firebear\ImportExport\Model\Import\Order\Shipment\Comment
     */
    protected $_shipmentComment;

    /**
     * Payment Entity Adapter
     *
     * @var Payment
     */
    protected $_payment;

    /**
     * Payment Transaction Entity Adapter
     *
     * @var Transaction
     */
    protected $_transaction;

    /**
     * Invoice Entity Adapter
     *
     * @var Invoice
     */
    protected $_invoice;

    /**
     * Invoice Item Entity Adapter
     *
     * @var \Firebear\ImportExport\Model\Import\Order\Invoice\Item
     */
    protected $_invoiceItem;

    /**
     * Invoice Comment Entity Adapter
     *
     * @var \Firebear\ImportExport\Model\Import\Order\Invoice\Comment
     */
    protected $_invoiceComment;

    /**
     * Creditmemo Entity Adapter
     *
     * @var Creditmemo
     */
    protected $_creditmemo;

    /**
     * Creditmemo Item Entity Adapter
     *
     * @var \Firebear\ImportExport\Model\Import\Order\Creditmemo\Item
     */
    protected $_creditmemoItem;

    /**
     * Creditmemo Comment Entity Adapter
     *
     * @var Comment
     */
    protected $_creditmemoComment;

    /**
     * Tax Entity Adapter
     *
     * @var Tax
     */
    protected $_tax;

    /**
     * Tax Item Adapter
     *
     * @var Item
     */
    protected $_taxItem;

    /**
     * Status History Entity Adapter
     *
     * @var \Firebear\ImportExport\Model\Import\Order\Status\History
     */
    protected $_statusHistory;

    /**
     * Grid Pool
     *
     * @var GridPool
     */
    protected $_gridPool;

    /**
     * Data Processor
     *
     * @var DataProcessor
     */
    protected $_dataProcessor;

    /**
     * @var TimezoneInterface
     */
    private $localeDate;

    /**
     * Order constructor.
     * @param Context $context
     * @param EntityFactory $entityFactory
     * @param ItemFactory $itemFactory
     * @param AddressFactory $addressFactory
     * @param ShipmentFactory $shipmentFactory
     * @param ShipmentItemFactory $shipmentItemFactory
     * @param ShipmentTrackFactory $shipmentTrackFactory
     * @param ShipmentCommentFactory $shipmentCommentFactory
     * @param PaymentFactory $paymentFactory
     * @param TransactionFactory $transactionFactory
     * @param InvoiceFactory $invoiceFactory
     * @param InvoiceItemFactory $invoiceItemFactory
     * @param InvoiceCommentFactory $invoiceCommentFactory
     * @param CreditmemoFactory $creditmemoFactory
     * @param CreditmemoItemFactory $creditmemoItemFactory
     * @param CreditmemoCommentFactory $creditmemoCommentFactory
     * @param TaxFactory $taxFactory
     * @param TaxItemFactory $taxItemFactory
     * @param StatusHistoryFactory $statusHistory
     * @param GridPool $gridPool
     * @param DataProcessor $dataProcessor
     * @param Helper $helper
     * @param TimezoneInterface $localeDate
     * @throws LocalizedException
     */
    public function __construct(
        Context $context,
        EntityFactory $entityFactory,
        ItemFactory $itemFactory,
        AddressFactory $addressFactory,
        ShipmentFactory $shipmentFactory,
        ShipmentItemFactory $shipmentItemFactory,
        ShipmentTrackFactory $shipmentTrackFactory,
        ShipmentCommentFactory $shipmentCommentFactory,
        PaymentFactory $paymentFactory,
        TransactionFactory $transactionFactory,
        InvoiceFactory $invoiceFactory,
        InvoiceItemFactory $invoiceItemFactory,
        InvoiceCommentFactory $invoiceCommentFactory,
        CreditmemoFactory $creditmemoFactory,
        CreditmemoItemFactory $creditmemoItemFactory,
        CreditmemoCommentFactory $creditmemoCommentFactory,
        TaxFactory $taxFactory,
        TaxItemFactory $taxItemFactory,
        StatusHistoryFactory $statusHistory,
        GridPool $gridPool,
        DataProcessor $dataProcessor,
        Helper $helper,
        TimezoneInterface $localeDate
    ) {
        $this->_logger = $context->getLogger();
        $this->_order = $entityFactory->create();
        $this->_item = $itemFactory->create();
        $this->_address = $addressFactory->create();
        $this->_shipment = $shipmentFactory->create();
        $this->_shipmentItem = $shipmentItemFactory->create();
        $this->_shipmentTrack = $shipmentTrackFactory->create();
        $this->_shipmentComment = $shipmentCommentFactory->create();
        $this->_payment = $paymentFactory->create();
        $this->_transaction = $transactionFactory->create();
        $this->_invoice = $invoiceFactory->create();
        $this->_invoiceItem = $invoiceItemFactory->create();
        $this->_invoiceComment = $invoiceCommentFactory->create();
        $this->_creditmemo = $creditmemoFactory->create();
        $this->_creditmemoItem = $creditmemoItemFactory->create();
        $this->_creditmemoComment = $creditmemoCommentFactory->create();
        $this->_tax = $taxFactory->create();
        $this->_taxItem = $taxItemFactory->create();
        $this->_statusHistory = $statusHistory->create();
        $this->_gridPool = $gridPool;
        $this->_dataProcessor = $dataProcessor;
        $this->_helper = $helper;
        $this->output = $context->getOutput();

        parent::__construct(
            $context->getJsonHelper(),
            $context->getImportExportData(),
            $context->getDataSourceModel(),
            $context->getConfig(),
            $context->getResource(),
            $context->getResourceHelper(),
            $context->getStringUtils(),
            $context->getErrorAggregator()
        );
        $this->localeDate = $localeDate;
    }

    /**
     * Retrieve All Fields Source
     *
     * @return array
     */
    public function getAllFields()
    {
        $fields = [];
        foreach ($this->getChildren() as $adapter) {
            $fields = array_merge($fields, $adapter->getAllFields());
        }
        return $fields;
    }

    /**
     * Retrieve Replacing Fields
     *
     * @return array
     */
    public function getReplacingFields()
    {
        $fields = [];
        foreach ($this->getChildren() as $adapter) {
            $fields = array_merge($fields, $adapter->getReplacingFields());
        }
        return $fields;
    }

    /**
     * Retrieve Children Adapters
     *
     * @return array
     */
    public function getChildren()
    {
        return [
            $this->_order,
            $this->_item,
            $this->_address,
            $this->_shipment,
            $this->_shipmentItem,
            $this->_shipmentTrack,
            $this->_shipmentComment,
            $this->_payment,
            $this->_transaction,
            $this->_invoice,
            $this->_invoiceItem,
            $this->_invoiceComment,
            $this->_creditmemo,
            $this->_creditmemoItem,
            $this->_creditmemoComment,
            $this->_tax,
            $this->_taxItem,
            $this->_statusHistory
        ];
    }

    /**
     * Import Data Rows
     *
     * @return boolean
     */
    protected function _importData()
    {
        $this->_dataProcessor->setFileName(
            $this->_dataSourceModel->getFile()
        );
        /* import data */
        $this->_connection->beginTransaction();
        try {
            if ($this->_order->importData()) {
                list($orderIds, $orderItemIds) = $this->_importOrderItem();
                $this->_importAddress($orderIds);
                $this->_importShipment($orderIds, $orderItemIds);
                $this->_importPayment($orderIds);
                $this->_importInvoice($orderIds, $orderItemIds);
                $this->_importCreditmemo($orderIds, $orderItemIds);
                $this->_importTax($orderIds, $orderItemIds);
                $this->_importStatusHistory($orderIds);
                /* refresh grid and grid archive(ee) */
                foreach ($orderIds as $incrementId => $orderId) {
                    $this->_gridPool->refreshByOrderId($orderId);
                    $this->addLogWriteln(__('order with order id: %1', $incrementId), $this->getOutput(), 'info');
                }
            }
            $this->_connection->commit();
        } catch (Exception $e) {
            $this->addLogWriteln(__('Sorry, but the data is invalid'), $this->output, 'error');
            $this->_connection->rollBack();
            return false;
        }
        return true;
    }

    /**
     * Import Order Item Data
     *
     * @return array
     */
    protected function _importOrderItem()
    {
        $orderIds = $this->_dataProcessor->merge(
            $this->_order->getOrderIdsMap(),
            'orderIds'
        );
        /* order item */
        $this->_item->setOrderIdsMap($orderIds)
            ->importData();

        $orderItemIds = $this->_dataProcessor->merge(
            $this->_item->getItemIdsMap(),
            'orderItemIds'
        );
        return [$orderIds, $orderItemIds];
    }

    /**
     * Import Address Data
     *
     * @param array $orderIds
     * @return void
     */
    protected function _importAddress(array $orderIds)
    {
        $this->_address->setOrderIdsMap($orderIds)
            ->importData();

        $this->createShippingAddresses($orderIds);
        $this->createAddresses($orderIds);
    }

    /**
     * Create shipping addresses
     *
     * @param $orderIds
     * @return void
     */
    protected function createShippingAddresses($orderIds)
    {
        $orderTable = $this->_address->getOrderTable();
        $addressTable = $this->_address->getMainTable();
        $select = $this->getConnection()
            ->select()
            ->from(
                ['o' => $orderTable],
                []
            )
            ->joinLeft(
                ['b' => $addressTable],
                'b.parent_id=o.entity_id AND b.address_type="billing"',
                ['b.*']
            )
            ->joinLeft(
                ['s' => $addressTable],
                's.parent_id=o.entity_id AND s.address_type="shipping"',
                []
            )
            ->where('s.entity_id IS NULL')
            ->where('o.is_virtual = ?', 0)
            ->where('o.entity_id IN (?)', $orderIds);

        try {
            $addresses = $this->getConnection()->fetchAll($select);
            foreach ($addresses as $address) {
                $address['entity_id'] = null;
                $address['address_type'] = 'shipping';
                $this->getConnection()->insert(
                    $addressTable,
                    $address
                );

                $addressId = $this->getConnection()->lastInsertId($addressTable);
                $this->getConnection()->update(
                    $orderTable,
                    ['shipping_address_id' => $addressId],
                    ['entity_id = ?' => $address['parent_id']]
                );
            }
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
    }

    /**
     * Create default orders addresses for orders without any address.
     * Order addresses are required entities.
     *
     * @param $orderIds
     *
     * @return array
     */
    protected function createAddresses($orderIds)
    {
        $ordersWoAddresses = [];
        $resultIds = [];
        $addressData = [];
        $orderTable = $this->_address->getOrderTable();
        $addressTable = $this->_address->getMainTable();

        $select = $this->getConnection()
            ->select()
            ->from(
                ['so' => $orderTable],
                ['entity_id', 'customer_id', 'customer_email', 'customer_firstname', 'customer_lastname']
            )
            ->joinLeft(
                ['soa' => $addressTable],
                'so.entity_id  = soa.parent_id',
                []
            )
            ->where('soa.entity_id IS NULL')
            ->where('so.entity_id IN (?)', $orderIds);

        try {
            $ordersWoAddresses = $this->getConnection()->fetchAll($select);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        foreach ($ordersWoAddresses as $row) {
            /**
             * @see \Magento\Sales\Model\Order\Payment::place() for required columns
             */
            $addressData[] = [
                'parent_id' => $row['entity_id'],
                'customer_address_id' => 0,
                'customer_id' => $row['customer_id'],
                'email' => $row['customer_email'],
                'firstname' => $row['customer_firstname'],
                'lastname' => $row['customer_lastname'],
                'address_type' => 'shipping'
            ];
            $addressData[] = [
                'parent_id' => $row['entity_id'],
                'customer_address_id' => 0,
                'customer_id' => $row['customer_id'],
                'email' => $row['customer_email'],
                'firstname' => $row['customer_firstname'],
                'lastname' => $row['customer_lastname'],
                'address_type' => 'billing'
            ];
        }

        if ($addressData) {
            foreach ($addressData as $data) {
                $this->getConnection()->insert(
                    $addressTable,
                    $data
                );
                $resultIds[] = $this->getConnection()->lastInsertId($addressTable);
            }
        }

        return $resultIds;
    }

    /**
     * Import Shipment Data
     *
     * @param array $orderIds
     * @param array $orderItemIds
     * @return void
     */
    protected function _importShipment(array $orderIds, array $orderItemIds)
    {
        $this->_shipment->setOrderIdsMap($orderIds)->importData();
        $shipmentIds = $this->_dataProcessor->merge(
            $this->_shipment->getShipmentIdsMap(),
            'shipmentIds'
        );
        /* shipment item */
        $this->_shipmentItem
            ->setShipmentIdsMap($shipmentIds)
            ->setItemIdsMap($orderItemIds)
            ->importData();
        /* shipment track */
        $this->_shipmentTrack
            ->setShipmentIdsMap($shipmentIds)
            ->setOrderIdsMap($orderIds)
            ->importData();
        /* shipment comment */
        $this->_shipmentComment
            ->setShipmentIdsMap($shipmentIds)
            ->importData();
    }

    /**
     * Import Payment Data
     *
     * @param array $orderIds
     * @return void
     */
    protected function _importPayment(array $orderIds)
    {
        $this->_payment->setOrderIdsMap($orderIds)->importData();

        $paymentIds = $this->_payment->getPaymentIdsMap();
        $newPaymentIds = $this->createPayments($orderIds);
        $paymentIds += $newPaymentIds;

        $this->_transaction
            ->setOrderIdsMap($orderIds)
            ->setPaymentIdsMap($paymentIds)
            ->setTransactionIdsMap([])
            ->importData();
    }

    /**
     * Create default payments for orders without any payment.
     * Order payments are required entities.
     *
     * @param $orderIds
     *
     * @return array
     */
    protected function createPayments($orderIds)
    {
        $ordersWoPayments = [];
        $resultIds = [];
        $paymentData = [];
        $orderTable = $this->_payment->getOrderTable();
        $paymentTable = $this->_payment->getMainTable();

        $select = $this->getConnection()
            ->select()
            ->from(
                ['so' => $orderTable],
                ['entity_id', 'total_due', 'base_total_due', 'shipping_amount', 'base_shipping_amount']
            )
            ->joinLeft(
                ['sop' => $paymentTable],
                'so.entity_id  = sop.parent_id',
                []
            )
            ->where('sop.entity_id IS NULL')
            ->where('so.entity_id IN (?)', $orderIds);

        try {
            $ordersWoPayments = $this->getConnection()->fetchAll($select);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        foreach ($ordersWoPayments as $row) {
            /**
             * @see \Magento\Sales\Model\Order\Payment::place() for required columns
             */
            $paymentData[] = [
                'parent_id' => $row['entity_id'],
                'amount_ordered' => $row['total_due'],
                'base_amount_ordered' => $row['base_total_due'],
                'shipping_amount' => $row['shipping_amount'],
                'base_shipping_amount' => $row['base_shipping_amount'],
                'method' => Checkmo::PAYMENT_METHOD_CHECKMO_CODE
            ];
        }

        if ($paymentData) {
            foreach ($paymentData as $data) {
                $this->getConnection()->insert(
                    $paymentTable,
                    $data
                );
                $resultIds[] = $this->getConnection()->lastInsertId($paymentTable);
            }
        }

        return $resultIds;
    }

    protected function getConnection()
    {
        return $this->_connection;
    }

    /**
     * Import Invoice Data
     *
     * @param array $orderIds
     * @param array $orderItemIds
     * @return void
     */
    protected function _importInvoice(array $orderIds, array $orderItemIds)
    {
        $this->_invoice->setOrderIdsMap($orderIds)->importData();
        $invoiceIds = $this->_dataProcessor->merge(
            $this->_invoice->getInvoiceIdsMap(),
            'invoiceIds'
        );
        /* invoice item */
        $this->_invoiceItem
            ->setInvoiceIdsMap($invoiceIds)
            ->setItemIdsMap($orderItemIds)
            ->importData();
        /* invoice comment */
        $this->_invoiceComment
            ->setInvoiceIdsMap($invoiceIds)
            ->importData();
    }

    /**
     * Import Creditmemo Data
     *
     * @param array $orderIds
     * @param array $orderItemIds
     * @return void
     */
    protected function _importCreditmemo(array $orderIds, array $orderItemIds)
    {
        $this->_creditmemo->setOrderIdsMap($orderIds)->importData();
        $creditmemoIds = $this->_dataProcessor->merge(
            $this->_creditmemo->getCreditmemoIdsMap(),
            'creditmemoIds'
        );
        /* creditmemo item */
        $this->_creditmemoItem
            ->setCreditmemoIdsMap($creditmemoIds)
            ->setItemIdsMap($orderItemIds)
            ->importData();
        /* creditmemo comment */
        $this->_creditmemoComment
            ->setCreditmemoIdsMap($creditmemoIds)
            ->importData();
    }

    /**
     * Import Tax Data
     *
     * @param array $orderIds
     * @param array $orderItemIds
     * @return void
     */
    protected function _importTax(array $orderIds, array $orderItemIds)
    {
        $this->_tax->setOrderIdsMap($orderIds)->importData();
        $taxIds = $this->_dataProcessor->merge(
            $this->_tax->getTaxIdsMap(),
            'taxIds'
        );
        /* tax item */
        $this->_taxItem
            ->setTaxIdsMap($taxIds)
            ->setItemIdsMap($orderItemIds)
            ->importData();
    }

    /**
     * Import Status History Data
     *
     * @param array $orderIds
     * @return void
     */
    protected function _importStatusHistory(array $orderIds)
    {
        $this->_statusHistory->setOrderIdsMap($orderIds)->importData();
    }

    /**
     * Validate Data Row
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return boolean
     */
    public function validateRow(array $rowData, $rowNumber)
    {
        foreach ($this->getChildren() as $adapter) {
            $tempData = $adapter->prepareRowData($rowData);

            /**
             * Check if array is not empty with array_filter function.
             */
            if ($tempData && array_filter($tempData) && !$adapter->validateRow($tempData, $rowNumber)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Retrieve Entity Type Code
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        return self::ENTITY_TYPE_CODE;
    }

    /**
     * @param array $rowData
     * @return array
     */
    protected function useOnlyFieldsFromMapping($rowData = [])
    {
        if (empty($this->_parameters['map'])) {
            return $rowData;
        }
        $rowDataAfterMapping = [];
        foreach ($this->_parameters['map'] as $parameter) {
            if (array_key_exists($parameter['import'], $rowData)) {
                $rowDataAfterMapping[$parameter['system']] = $rowData[$parameter['import']];
            }
        }
        return $rowDataAfterMapping;
    }

    /**
     * Save Validated Bunches
     *
     * @return $this
     * @throws LocalizedException
     */
    protected function _saveValidatedBunches()
    {
        $source = $this->_getSource();
        $currentDataSize = 0;
        $bunchRows = [];
        $startNewBunch = false;
        $nextRowBackup = [];
        $maxDataSize = $this->_resourceHelper->getMaxDataSize();
        $bunchSize = $this->_importExportData->getBunchSize();

        $source->rewind();
        $this->_dataSourceModel->cleanBunches();
        $file = null;
        $jobId = null;
        if (isset($this->_parameters['file'])) {
            $file = $this->_parameters['file'];
        }
        if (isset($this->_parameters['job_id'])) {
            $jobId = $this->_parameters['job_id'];
        }

        while ($source->valid() || $bunchRows) {
            if ($startNewBunch || !$source->valid()) {
                $this->_dataSourceModel->saveBunches(
                    $this->getEntityTypeCode(),
                    $this->getBehavior(),
                    $jobId,
                    $file,
                    $bunchRows
                );
                $bunchRows = $nextRowBackup;
                $currentDataSize = strlen($this->jsonHelper->jsonEncode($bunchRows));
                $startNewBunch = false;
                $nextRowBackup = [];
            }

            if ($source->valid()) {
                try {
                    $rowData = $source->current();
                } catch (InvalidArgumentException $e) {
                    $this->addRowError($e->getMessage(), $this->_processedRowsCount);
                    $this->_processedRowsCount++;
                    $source->next();
                    continue;
                }
                if (!empty($this->_parameters['use_only_fields_from_mapping'])) {
                    $rowData = $this->useOnlyFieldsFromMapping($rowData);
                }
                $rowData = $this->customBunchesData($rowData);
                $this->_processedRowsCount++;
                if ($this->validateRow($rowData, $source->key())) {
                    $rowSize = strlen($this->jsonHelper->jsonEncode($rowData));

                    $isBunchSizeExceeded = $bunchSize > 0 && count($bunchRows) >= $bunchSize;

                    if (!empty($rowData['increment_id']) &&
                        ($currentDataSize + $rowSize >= $maxDataSize || $isBunchSizeExceeded)
                    ) {
                        $startNewBunch = true;
                        $nextRowBackup = [$source->key() => $rowData];
                    } else {
                        $bunchRows[$source->key()] = $rowData;
                        $currentDataSize += $rowSize;
                    }
                }
                $source->next();
            }
        }
        return $this;
    }

    /**
     * Output Model Setter
     *
     * @param $output
     * @return $this
     */
    public function setOutput($output)
    {
        $this->output = $output;
        foreach ($this->getChildren() as $adapter) {
            $adapter->setOutput($output);
        }
        return $this;
    }

    /**
     * Logger Model Setter
     *
     * @param $logger
     * @return $this
     */
    public function setLogger($logger)
    {
        $this->_logger = $logger;
        foreach ($this->getChildren() as $adapter) {
            $adapter->setLogger($logger);
        }
        return $this;
    }

    /**
     * Set Data From Outside To Change Behavior
     *
     * @param array $parameters
     * @return $this
     */
    public function setParameters(array $parameters)
    {
        parent::setParameters($parameters);
        foreach ($this->getChildren() as $adapter) {
            $adapter->setParameters($parameters);
        }
        return $this;
    }

    /**
     * Source Model Setter
     *
     * @param AbstractSource $source
     * @return \Magento\ImportExport\Model\Import\AbstractEntity|AbstractEntity
     */
    public function setSource(AbstractSource $source)
    {
        foreach ($this->getChildren() as $adapter) {
            $adapter->setSource($source);
        }
        return parent::setSource($source);
    }

    /**
     * Error Aggregator Setter
     *
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @return $this
     */
    public function setErrorAggregator($errorAggregator)
    {
        $this->errorAggregator = $errorAggregator;
        foreach ($this->getChildren() as $adapter) {
            $adapter->setErrorAggregator($errorAggregator);
            $adapter->initErrorTemplates();
        }
        return $this;
    }

    /**
     * Returns Number Of Checked Entities
     *
     * @return int
     */
    public function getProcessedEntitiesCount()
    {
        $this->_processedEntitiesCount = $this->_order->getProcessedEntitiesCount();
        foreach ($this->getChildren() as $adapter) {
            $this->_processedEntitiesCount = max(
                $this->_processedEntitiesCount,
                $adapter->getProcessedEntitiesCount()
            );
        }
        return $this->_processedEntitiesCount;
    }

    /**
     * @param array $rowData
     * @return mixed
     * @throws LocalizedException
     */
    public function customBunchesData(array $rowData)
    {
        $dateFormat = 'Y-m-d H:i:s';
        foreach (['created_at', 'updated_at'] as $key) {
            if (isset($rowData[$key]) && !empty($rowData[$key])) {
                $originalDate = str_replace('/', '-', $rowData[$key]);
                if (strtotime($originalDate)) {
                    $rowData[$key] = $this->localeDate->convertConfigTimeToUtc($originalDate, $dateFormat);
                }
            }
        }
        if (isset($rowData['shipping_method']) && !empty($rowData['shipping_method'])) {
            $rowData['shipping_method'] = str_replace(' ', '_', $rowData['shipping_method']);
        }
        return $rowData;
    }
}
