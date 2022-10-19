<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import;

use Firebear\ImportExport\Traits\Import\Entity as ImportTrait;
use Magento\CustomerImportExport\Model\Import\Customer as MagentoCustomer;
use Symfony\Component\Console\Output\ConsoleOutput;
use \Magento\ImportExport\Model\Import\AbstractEntity;
use Firebear\ImportExport\Model\Import;

/**
 * Class Customer
 *
 * @package Firebear\ImportExport\Model\Import
 */
class Customer extends MagentoCustomer
{
    use ImportTrait;

    protected $_debugMode;

    /**
     * @var array
     */
    protected $superUserList = [];

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;

    /**
     * @param Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\ImportExport\Model\ImportFactory $importFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\ImportExport\Model\Export\Factory $collectionFactory
     * @param \Magento\CustomerImportExport\Model\ResourceModel\Import\Customer\StorageFactory $storageFactory
     * @param \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory $attrCollectionFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param ConsoleOutput $output
     * @param \Firebear\ImportExport\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\ImportExport\Model\ImportFactory $importFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\ImportExport\Model\Export\Factory $collectionFactory,
        \Magento\CustomerImportExport\Model\ResourceModel\Import\Customer\StorageFactory $storageFactory,
        \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory $attrCollectionFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        ConsoleOutput $output,
        \Firebear\ImportExport\Helper\Data $helper,
        array $data = []
    ) {
        parent::__construct(
            $context->getStringUtils(),
            $scopeConfig,
            $importFactory,
            $context->getResourceHelper(),
            $context->getResource(),
            $context->getErrorAggregator(),
            $storeManager,
            $collectionFactory,
            $context->getConfig(),
            $storageFactory,
            $attrCollectionFactory,
            $customerFactory,
            $data
        );
        $this->_availableBehaviors = [
            \Magento\ImportExport\Model\Import::BEHAVIOR_APPEND,
            \Magento\ImportExport\Model\Import::BEHAVIOR_DELETE,
            \Magento\ImportExport\Model\Import::BEHAVIOR_REPLACE,
        ];
        $this->output = $output;
        $this->_logger = $context->getLogger();
        $this->_debugMode = $helper->getDebugMode();
        $this->_dataSourceModel = $context->getDataSourceModel();
        $this->_resource = $context->getResource();
        $this->_helper = $helper;
    }

    /**
     * @return array
     */
    public function getAllFields()
    {
        $options = array_merge($this->getValidColumnNames(), $this->_specialAttributes);
        $options = array_merge($options, $this->_permanentAttributes);

        return array_unique($options);
    }

    protected function _importData()
    {
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $entitiesCreate = [];
            $entitiesUpdate = [];
            $entitiesDelete = [];
            $attributesToSave = [];

            foreach ($bunch as $rowNumber => $rowData) {
                $time = explode(" ", microtime());
                $startTime = $time[0] + $time[1];
                $email = $rowData['email'];
                $rowData = $this->joinIdenticalyData($rowData);
                $website = $rowData[self::COLUMN_WEBSITE];
                if (isset($this->_newCustomers[strtolower($rowData[self::COLUMN_EMAIL])][$website])) {
                    continue;
                }
                $rowData = $this->customChangeData($rowData);
                if (!$this->validateRow($rowData, $rowNumber)) {
                    $this->addLogWriteln(__('customer with email: %1 is not valided', $email), $this->output, 'info');
                    continue;
                }
                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNumber);
                    continue;
                }

                if (\Magento\ImportExport\Model\Import::BEHAVIOR_DELETE == $this->getBehavior($rowData)) {
                    $entitiesDelete[] = $this->_getCustomerId(
                        $rowData[self::COLUMN_EMAIL],
                        $rowData[self::COLUMN_WEBSITE]
                    );
                }
                if (\Magento\ImportExport\Model\Import::BEHAVIOR_ADD_UPDATE == $this->getBehavior($rowData)
                    || \Magento\ImportExport\Model\Import::BEHAVIOR_REPLACE == $this->getBehavior($rowData)
                ) {
                    $processedData = $this->_prepareDataForUpdate($rowData);
                    if (\Magento\ImportExport\Model\Import::BEHAVIOR_ADD_UPDATE == $this->getBehavior($rowData)) {
                        $entitiesCreate = array_merge($entitiesCreate, $processedData[self::ENTITIES_TO_CREATE_KEY]);
                    }
                    $entitiesUpdate = array_merge($entitiesUpdate, $processedData[self::ENTITIES_TO_UPDATE_KEY]);
                    foreach ($processedData[self::ATTRIBUTES_TO_SAVE_KEY] as $tableName => $customerAttributes) {
                        if (!isset($attributesToSave[$tableName])) {
                            $attributesToSave[$tableName] = [];
                        }
                        $attributesToSave[$tableName] =
                            array_diff_key(
                                $attributesToSave[$tableName],
                                $customerAttributes
                            ) + $customerAttributes;
                    }
                }
                $time = explode(" ", microtime());
                $endTime = $time[0] + $time[1];
                $totalTime = $endTime - $startTime;
                $totalTime = round($totalTime, 5);

                $this->addLogWriteln(
                    __('customer with email: %1 .... %2s', $email, $totalTime),
                    $this->output,
                    'info'
                );
            }
            $this->updateItemsCounterStats($entitiesCreate, $entitiesUpdate, $entitiesDelete);
            /**
             * Save prepared data
             */
            if ($entitiesCreate || $entitiesUpdate) {
                $this->_saveCustomerEntities($entitiesCreate, $entitiesUpdate);
            }
            if ($attributesToSave) {
                $this->_saveCustomerAttributes($attributesToSave);
            }
            if ($entitiesDelete) {
                $this->_deleteCustomerEntities($entitiesDelete);
            }
        }

        return true;
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
        $rowDataAfterMapping = [];
        foreach ($this->_parameters['map'] as $parameter) {
            if (array_key_exists($parameter['import'], $rowData)) {
                $rowDataAfterMapping[$parameter['system']] = $rowData[$parameter['import']];
            }
        }
        foreach ($requiredFields as $k => $value) {
            $rowDataAfterMapping[$k] = !empty($rowData[$k]) ? $rowData[$k] : $value;
        }
        if (empty($rowDataAfterMapping['email'])) {
            $this->addRowError(
                "Required field email is not mapped. Please, complete mapping and retry import.",
                $this->_processedRowsCount
            );
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
        $isSuperUserList = $this->initSuperUserListProcess();
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
                    if (!empty($this->_parameters['use_only_fields_from_mapping'])) {
                        $rowData = $this->useOnlyFieldsFromMapping($rowData);
                    }
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
                if (!$valid) {
                    $this->_processedRowsCount++;
                    $source->next();
                    continue;
                }
                $rowData = $this->customBunchesData($rowData);
                if ($isSuperUserList) {
                    $this->checkSuperUser($rowData, $source->key());
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

                if (isset($entityGroup) && $this->validateRow($rowData, $source->key())) {
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
     * @return bool
     */
    protected function initSuperUserListProcess()
    {
        $result = false;
        if (\Magento\ImportExport\Model\Import::BEHAVIOR_DELETE == $this->getBehavior()
            && $this->_connection->isTableExists('company')
        ) {
            $tableName = $this->_resource->getTableName('company');
            $select = $this->_connection->select();
            $select->from(['c' => $tableName], 'c.super_user_id');
            try {
                $data = $this->_connection->fetchAll($select);
            } catch (\Exception $e) {
                $this->_logger->error($e->getMessage());
            }
            if (!empty($data)) {
                foreach ($data as $row) {
                    $this->superUserList[$row['super_user_id']] = 1;
                }
                $result = true;
            }
        }
        return $result;
    }

    /**
     * @param $customerId
     * @return int
     */
    protected function isSuperUser($customerId)
    {
        return isset($this->superUserList[$customerId]) ? 1 : 0;
    }

    /**
     * @param $rowData
     * @param $rowNum
     */
    protected function checkSuperUser($rowData, $rowNum)
    {
        if (isset($rowData[self::COLUMN_EMAIL]) && isset($rowData[self::COLUMN_WEBSITE])) {
            $customerId = $this->_getCustomerId(
                $rowData[self::COLUMN_EMAIL],
                $rowData[self::COLUMN_WEBSITE]
            );
            if ($this->isSuperUser($customerId)) {
                $email = $rowData[self::COLUMN_EMAIL];
                $message = 'Cannot delete the company admin. Customer with email: %1 is company admin.';
                $this->addLogWriteln(__($message, $email), $this->output, 'error');
                $this->addRowError(__($message, $email), $rowNum);
            }
        }
    }

    protected function _validateRowForUpdate(array $rowData, $rowNum)
    {
        if ($this->_checkUniqueKey($rowData, $rowNum)) {
            $email = strtolower($rowData[self::COLUMN_EMAIL]);
            $website = $rowData[self::COLUMN_WEBSITE];
            $this->_newCustomers[$email][$website] = false;

            if (!empty($rowData[self::COLUMN_STORE]) && !isset($this->_storeCodeToId[$rowData[self::COLUMN_STORE]])) {
                $this->addRowError(self::ERROR_INVALID_STORE, $rowNum);
            }
            if (isset($rowData['password']) && strlen($rowData['password'])
                && $this->string->strlen($rowData['password']) < self::MIN_PASSWORD_LENGTH
            ) {
                $this->addRowError(self::ERROR_PASSWORD_LENGTH, $rowNum);
            }
            foreach ($this->_attributes as $attributeCode => $attributeData) {
                if (in_array($attributeCode, $this->_ignoredAttributes)) {
                    continue;
                }
                if (isset($rowData[$attributeCode]) && strlen($rowData[$attributeCode])) {
                    $this->isAttributeValid(
                        $attributeCode,
                        $attributeData,
                        $rowData,
                        $rowNum,
                        isset($this->_parameters[Import::FIELD_FIELD_MULTIPLE_VALUE_SEPARATOR])
                            ? $this->_parameters[Import::FIELD_FIELD_MULTIPLE_VALUE_SEPARATOR]
                            : Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR
                    );
                } elseif ($attributeData['is_required'] && !$this->_getCustomerId($email, $website)) {
                    $this->addRowError(self::ERROR_VALUE_IS_REQUIRED, $rowNum, $attributeCode);
                }
            }
        }
    }

    /**
     * Initialize entity attributes
     *
     * @return $this
     */
    protected function _initAttributes()
    {
        $this->_attributes['confirmation'] = [
            'id' => null,
            'code' => 'confirmation',
            'table' => '',
            'is_required' => false,
            'is_static' => true,
            'rules' => null,
            'type' => 'static'
        ];
        $this->validColumnNames[] = 'confirmation';

        return parent::_initAttributes();
    }
}
