<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import;

use Firebear\ImportExport\Model\ResourceModel\Import\CustomerComposite\Data as CustomerCompositeData;
use Firebear\ImportExport\Traits\Import\Entity as ImportTrait;
use Magento\Customer\Model\Indexer\Processor;
use Magento\CustomerImportExport\Model\Import\CustomerComposite as MagentoCustomer;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ProductMetadataInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use \Magento\ImportExport\Model\Import\AbstractEntity;

/**
 * Class CustomerComposite
 *
 * @package Firebear\ImportExport\Model\Import
 */
class CustomerComposite extends MagentoCustomer
{
    use ImportTrait;

    protected $specialFields = [
        'reward_update_notification',
        'reward_warning_notification'
    ];

    protected $_debugMode;

    protected $_customerEntity;

    protected $_addressAttributes = [
        'increment_id'
    ];

    /**
     * @var
     */
    protected $indexerProcessor;

    /**
     * @var CustomerCompositeData
     */
    protected $_dataSourceModel;

    /**
     * @param Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\ImportExport\Model\ImportFactory $importFactory
     * @param \Magento\CustomerImportExport\Model\ResourceModel\Import\CustomerComposite\DataFactory $dataFactory
     * @param \Magento\CustomerImportExport\Model\Import\CustomerFactory $customerFactory
     * @param \Magento\CustomerImportExport\Model\Import\AddressFactory $addressFactory
     * @param ConsoleOutput $output
     * @param \Firebear\ImportExport\Helper\Data $helper
     * @param \Firebear\ImportExport\Model\Import\CustomerFactory $fireImportCustomer
     * @param AddressFactory $fireImportAddress
     * @param \Firebear\ImportExport\Model\ResourceModel\Import\CustomerComposite\DataFactory $importFireData
     * @param ProductMetadataInterface $productMetadata
     * @param array $data
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\ImportExport\Model\ImportFactory $importFactory,
        \Magento\CustomerImportExport\Model\ResourceModel\Import\CustomerComposite\DataFactory $dataFactory,
        \Magento\CustomerImportExport\Model\Import\CustomerFactory $customerFactory,
        \Magento\CustomerImportExport\Model\Import\AddressFactory $addressFactory,
        ConsoleOutput $output,
        \Firebear\ImportExport\Helper\Data $helper,
        \Firebear\ImportExport\Model\Import\CustomerFactory $fireImportCustomer,
        \Firebear\ImportExport\Model\Import\AddressFactory $fireImportAddress,
        \Firebear\ImportExport\Model\ResourceModel\Import\CustomerComposite\DataFactory $importFireData,
        ProductMetadataInterface $productMetadata,
        array $data = []
    ) {
        if (class_exists(Processor::class)
            && version_compare($productMetadata->getVersion(), '2.3.5', '>=')
        ) {
            $indexerProcessor = ObjectManager::getInstance()->create(Processor::class);
            $this->indexerProcessor = $indexerProcessor;
            parent::__construct(
                $context->getStringUtils(),
                $scopeConfig,
                $importFactory,
                $context->getResourceHelper(),
                $context->getResource(),
                $context->getErrorAggregator(),
                $dataFactory,
                $customerFactory,
                $addressFactory,
                $indexerProcessor,
                $data
            );
        } else {
            parent::__construct(
                $context->getStringUtils(),
                $scopeConfig,
                $importFactory,
                $context->getResourceHelper(),
                $context->getResource(),
                $context->getErrorAggregator(),
                $dataFactory,
                $customerFactory,
                $addressFactory,
                $data
            );
        }

        $this->_availableBehaviors = [
            \Magento\ImportExport\Model\Import::BEHAVIOR_APPEND,
            \Magento\ImportExport\Model\Import::BEHAVIOR_DELETE,
            \Magento\ImportExport\Model\Import::BEHAVIOR_REPLACE,
        ];
        $this->output = $output;
        $this->_logger = $context->getLogger();
        $this->_debugMode = $helper->getDebugMode();
        $this->_customerEntity = $fireImportCustomer->create(['data' => $data]);

        // Exclude common fields in customer and address tables
        $customerAttributes = array_filter($this->_customerAttributes, function ($value) {
            return !in_array($value, ['firstname', 'lastname']);
        });

        // address entity stuff
        $data['data_source_model'] = $importFireData->create(
            [
                'arguments' => [
                    'entity_type' => 'address',
                    'customer_attributes' => $customerAttributes,
                ],
            ]
        );
        $this->_addressEntity = $fireImportAddress->create(['data' => $data]);
        unset($data['data_source_model']);
        $this->_dataSourceModel = $importFireData->create();
        $this->_helper = $helper;
    }

    /**
     * @return array
     */
    public function getAllFields()
    {
        $options = array_merge($this->getValidColumnNames(), $this->_specialAttributes);
        $options = array_merge($options, $this->_permanentAttributes);
        $options = array_merge($options, $this->specialFields);

        return array_unique($options);
    }

    public function setLogger($logger)
    {
        $this->_logger = $logger;
        $this->_customerEntity->setLogger($logger);
        $this->_addressEntity->setLogger($logger);
    }

    /**
     * Import data rows
     *
     * @return bool
     */
    protected function _importData()
    {
        $this->_customerEntity->setDataSourceData(
            $this->_dataSourceModel->getFile(),
            $this->_dataSourceModel->getJobId(),
            $this->_dataSourceModel->getOffset()
        );
        $result = $this->_customerEntity->importData();
        if ($this->getBehavior() != \Magento\ImportExport\Model\Import::BEHAVIOR_DELETE) {
            $this->_addressEntity->setDataSourceData(
                $this->_dataSourceModel->getFile(),
                $this->_dataSourceModel->getJobId(),
                $this->_dataSourceModel->getOffset()
            );
            return $result && $this->_addressEntity->setCustomerAttributes($this->_customerAttributes)->importData();
        }

        return $result;
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
        $requiredFields = ['_website' => 'base'];
        $validatedFields = ['email'];
        $rowDataAfterMapping = [];
        foreach ($this->_parameters['map'] as $parameter) {
            if (array_key_exists($parameter['import'], $rowData)) {
                $rowDataAfterMapping[$parameter['system']] = $rowData[$parameter['import']];
            }
        }
        foreach ($requiredFields as $k => $value) {
            $rowDataAfterMapping[$k] = !empty($rowData[$k]) ? $rowData[$k] : $value;
        }
        foreach ($validatedFields as $field) {
            if (empty($rowDataAfterMapping[$field])) {
                $this->addRowError(
                    "Required field {$field} is not mapped. Please, complete mapping and retry import.",
                    $this->_processedRowsCount
                );
            }
        }
        return $rowDataAfterMapping;
    }

    protected function _saveValidatedBunches()
    {
        $source = $this->getSource();
        $bunchRows = [];
        $startNewBunch = false;

        $source->rewind();
        $this->_dataSourceModel->cleanBunches();
        $masterAttributeCode = $this->getMasterAttributeCode();
        $file = null;
        $jobId = null;
        if (isset($this->_parameters['file'])) {
            $file = $this->_parameters['file'];
        }
        if (isset($this->_parameters['job_id'])) {
            $jobId = $this->_parameters['job_id'];
        }
        $prevData = [];
        while ($source->valid() || count($bunchRows) || isset($entityGroup)) {
            if ($startNewBunch || !$source->valid()) {
                /* If the end approached add last validated entity group to the bunch */
                if (!$source->valid() && isset($entityGroup)) {
                    foreach ($entityGroup as $key => $value) {
                        $bunchRows[$key] = $value;
                    }
                    unset($entityGroup);
                }
                $this->_dataSourceModel->saveBunches(
                    $this->getEntityTypeCode(),
                    $this->getBehavior(),
                    $jobId,
                    $file,
                    $bunchRows
                );

                $bunchRows = [];
                $startNewBunch = false;
            }
            if ($source->valid()) {
                $valid = true;
                try {
                    $rowData = $source->current();
                    $rowData = $this->prepareAddressRow($rowData);
                    foreach ($rowData as $attrName => $element) {
                        if (!mb_check_encoding($element, 'UTF-8')) {
                            $valid = false;
                            $this->addRowError(
                                AbstractEntity::ERROR_CODE_ILLEGAL_CHARACTERS,
                                $this->_processedRowsCount,
                                $attrName
                            );
                        }
                    }
                } catch (\InvalidArgumentException $e) {
                    $valid = false;
                    $this->addRowError($e->getMessage(), $this->_processedRowsCount);
                }

                if (!empty($prevData) && (!isset($rowData['email']) || empty($rowData['email']))) {
                    $rowData = array_merge($prevData, $this->deleteEmpty($rowData));
                }

                $prevData = $rowData;

                if (!$valid) {
                    $this->_processedRowsCount++;
                    $source->next();
                    continue;
                }

                if (isset($rowData[$masterAttributeCode]) && trim($rowData[$masterAttributeCode])) {
                    /* Add entity group that passed validation to bunch */
                    if (isset($entityGroup)) {
                        foreach ($entityGroup as $key => $value) {
                            $bunchRows[$key] = $value;
                        }
                        $productDataSize = strlen($this->phpSerialize($bunchRows));

                        /* Check if the new bunch should be started */
                        $isBunchSizeExceeded = ($this->_bunchSize > 0 && count($bunchRows) >= $this->_bunchSize);
                        $startNewBunch = $productDataSize >= $this->_maxDataSize || $isBunchSizeExceeded;
                    }

                    /* And start a new one */
                    $entityGroup = [];
                }

                $isValid = $this->validateRow($rowData, $source->key());
                if (!$isValid) {
                    $errors = $this->getErrorAggregator()->getErrorByRowNumber($source->key());
                    foreach ($errors as $error) {
                        $this->addLogWriteln(
                            __('error from customer with email: %1. %2', $rowData['email'], $error->getErrorMessage()),
                            $this->output
                        );
                    }
                }
                if (!empty($this->_parameters['use_only_fields_from_mapping'])) {
                    $rowData = $this->useOnlyFieldsFromMapping($rowData);
                }
                if (isset($entityGroup) && $isValid) {
                    /* Add row to entity group */
                    $entityGroup[$source->key()] = $this->_prepareRowForDb($rowData);
                } elseif (isset($entityGroup)) {
                    /* In case validation of one line of the group fails kill the entire group */
                    unset($entityGroup);
                }

                $this->_processedRowsCount++;
                $source->next();
            }
        }
        return $this;
    }

    /**
     * Validate address row
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return bool
     */
    protected function _validateAddressRow(array $rowData, $rowNumber)
    {
        $isValid = parent::_validateAddressRow($rowData, $rowNumber);
        if (!$isValid) {
            $errors = $this->_addressEntity->getErrorAggregator()->getErrorByRowNumber($rowNumber);
            foreach ($errors as $error) {
                $this->addLogWriteln(
                    __('error from customer with email: %1. %2', $rowData['email'], $error->getErrorMessage()),
                    $this->output
                );
            }
        }
        return $isValid;
    }

    protected function deleteEmpty($array)
    {
        if (isset($array['sku'])) {
            unset($array['sku']);
        }
        $newElement = [];
        foreach ($array as $key => $element) {
            if (strlen($element)) {
                $newElement[$key] = $element;
            }
        }

        return $newElement;
    }

    /**
     * @param array $rowData
     * @return array
     */
    protected function prepareAddressRow(array &$rowData)
    {
        $prefix = static::COLUMN_ADDRESS_PREFIX;
        foreach ($this->_addressEntity->getAllFields() as $field) {
            if (!isset($rowData[$prefix . $field])
                && $this->string->strpos($field, $prefix) === false
                && isset($rowData[$field])
            ) {
                $rowData[$prefix . $field] = $rowData[$field];
            }
        }
        return $rowData;
    }

    /**
     * @return mixed
     */
    public function _getCustomerAttributes()
    {
        return array_merge($this->_customerAttributes, $this->_addressAttributes);
    }

    /**
     * @param array $rowData
     * @return mixed
     */
    public function prepareRowForDb(array $rowData)
    {
        return $this->_prepareRowForDb($rowData);
    }
}
