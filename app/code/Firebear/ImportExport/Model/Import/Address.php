<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import;

use Exception;
use Firebear\ImportExport\Helper\Data as HelperData;
use Firebear\ImportExport\Model\ResourceModel\Import\CustomerComposite\Data as CustomerCompositeData;
use Firebear\ImportExport\Traits\Import\Entity as ImportTrait;
use Magento\CustomerImportExport\Model\Import\Address as MagentoAddress;
use Magento\Framework\App\ObjectManager;
use Magento\ImportExport\Model\Import\AbstractEntity;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class Address
 *
 * @package Firebear\ImportExport\Model\Import
 */
class Address extends MagentoAddress
{
    use ImportTrait;

    /**
     * Customers Ids
     *
     * @var array[]
     */
    protected $_customerIds = [];

    /**
     * @var CustomerCompositeData
     */
    protected $_dataSourceModel;

    /**
     * @var array
     */
    protected $comparableList = [];

    /**
     * @return MagentoAddress|void
     */
    protected function _initAttributes()
    {
        $objectManager = ObjectManager::getInstance();
        $this->output = $objectManager->get(ConsoleOutput::class);

        if ($this->_dataSourceModel instanceof \Magento\ImportExport\Model\ResourceModel\Import\Data) {
            $this->_dataSourceModel = $objectManager
                ->create(CustomerCompositeData::class, [
                    'arguments' => [
                        'entity_type' => 'address'
                    ],
                ]);
        }

        if (!$this->_logger) {
            $this->_logger = $objectManager->get(LoggerInterface::class);
        }
        $this->_helper = $objectManager->get(HelperData::class);

        $this->_attributes['increment_id'] = [
            'code' => 'increment_id',
            'is_required' => false,
            'type' => 'int',
            'is_static' => true
        ];

        parent::_initAttributes();
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

    public function customChangeData($rowData)
    {
        // Add _entity_id if field increment_id exists
        $columnIncrementId = 'increment_id';
        if (!empty($rowData[$columnIncrementId])) {
            $email = strtolower($rowData[self::COLUMN_EMAIL]);
            $website = $rowData[self::COLUMN_WEBSITE];
            $parentId = $this->_getCustomerId($email, $website);

            if ($parentId) {
                $select = $this->_connection->select()
                    ->from($this->_entityTable, ['entity_id'])
                    ->where($columnIncrementId . ' = ?', $rowData[$columnIncrementId])
                    ->where('parent_id = ?', $parentId);

                $entityId = $this->_connection->fetchOne($select);

                if ($entityId) {
                    $rowData[static::COLUMN_ADDRESS_ID] = $entityId;
                }
            }
        }

        return $rowData;
    }

    /**
     * @param $rowData
     * @return $this
     */
    public function removeCustomerAddress($rowData)
    {
        $email = $rowData[self::COLUMN_EMAIL] ?? '';
        $website = $rowData[self::COLUMN_WEBSITE] ?? '';
        if (!empty($email) && !empty($website)) {
            $customerId = $this->_getCustomerId($rowData[self::COLUMN_EMAIL], $rowData[self::COLUMN_WEBSITE]);
            if ($customerId) {
                try {
                    $this->_connection->delete(
                        $this->_entityTable,
                        ['parent_id IN (?)' => $customerId]
                    );
                } catch (Exception $e) {
                    $this->addLogWriteln($e->getMessage(), $this->output, 'error');
                }
            }
        }

        return $this;
    }

    /**
     * @param array $rowData
     * @return array
     */
    protected function useOnlyFieldsFromMapping($rowData = [])
    {
        if (empty($this->_parameters['map']) || $this->_parameters['entity'] !== 'customer_address') {
            return $rowData;
        }
        $requiredFields = ['_website' => 'base', '_entity_id' => ''];
        $validatedFields = ['_email', '_entity_id'];
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

    /**
     * @return bool
     */
    protected function _importData()
    {
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $newRows = [];
            $updateRows = [];
            $attributes = [];
            $defaults = [];
            $deleteRowIds = [];
            if (\method_exists($this, 'prepareCustomerData')) {
                $this->prepareCustomerData($bunch);
            }
            foreach ($bunch as $rowNumber => $rowData) {
                $time = explode(" ", microtime());
                $startTime = $time[0] + $time[1];
                $email = $rowData['_email'];
                $rowData = $this->joinIdenticalyData($rowData);
                $rowData = $this->customChangeData($rowData);
                if (!empty($this->_parameters['use_only_fields_from_mapping'])) {
                    $rowData = $this->useOnlyFieldsFromMapping($rowData);
                    $bunch[$rowNumber] = $rowData;
                }
                if ($this->_isOptionalAddressEmpty($rowData) || !$this->validateRow($rowData, $rowNumber)) {
                    $this->addLogWriteln(__('address with email: %1 is not valided', $email), $this->output, 'info');
                    continue;
                }
                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNumber);
                    continue;
                }
                if (isset($this->_parameters['remove_all_customer_address'])
                    && $this->_parameters['remove_all_customer_address'] == 1
                ) {
                    $this->removeCustomerAddress($rowData);
                }
                if (\Magento\ImportExport\Model\Import::BEHAVIOR_ADD_UPDATE == $this->getBehavior($rowData)) {
                    $updateResult = $this->_prepareDataForUpdate($rowData);
                    if ($updateResult['entity_row_new']) {
                        $newRows[] = $updateResult['entity_row_new'];
                    }
                    if ($updateResult['entity_row_update']) {
                        $updateRows[] = $updateResult['entity_row_update'];
                    }
                    $attributes = $this->_mergeEntityAttributes($updateResult['attributes'], $attributes);
                    $defaults = $this->_mergeEntityAttributes($updateResult['defaults'], $defaults);
                } elseif ($this->getBehavior($rowData) == \Magento\ImportExport\Model\Import::BEHAVIOR_DELETE) {
                    $deleteRowIds[] = $rowData[self::COLUMN_ADDRESS_ID];
                }
                $endTime = $time[0] + $time[1];
                $totalTime = $endTime - $startTime;
                $totalTime = round($totalTime, 5);
                $this->addLogWriteln(
                    __('address with email: %1 .... %2s', $email, $totalTime),
                    $this->output,
                    'info'
                );
            }
            $this->updateItemsCounterStats(
                $newRows,
                $updateRows,
                $deleteRowIds
            );
            $this->_saveAddressEntities(
                $newRows,
                $updateRows
            )->_saveAddressAttributes(
                $attributes
            )->_saveCustomerDefaults(
                $defaults
            );

            $this->_deleteAddressEntities($deleteRowIds);
        }
        return true;
    }

    /**
     * Set customer id
     *
     * @param string $email
     * @param string $websiteCode
     * @param integer $customerId
     * @return $this
     */
    public function setCustomerId($email, $websiteCode, $customerId)
    {
        $email = strtolower(trim($email));
        $this->_customerIds[$email][$websiteCode] = $customerId;
        return $this;
    }

    /**
     * Get customer id if customer is present in database
     *
     * @param string $email
     * @param string $websiteCode
     * @return bool|int
     */
    protected function _getCustomerId($email, $websiteCode)
    {
        $email = strtolower(trim($email));
        if (isset($this->_websiteCodeToId[$websiteCode])) {
            $websiteId = $this->_websiteCodeToId[$websiteCode];
            if (isset($this->_customerIds[$email][$websiteId])) {
                return $this->_customerIds[$email][$websiteId];
            }
        }
        return parent::_getCustomerId($email, $websiteCode);
    }

    public function _prepareDataForUpdate(array $rowData):array
    {
        $updateData = parent::_prepareDataForUpdate($rowData);
        if ($updateData['entity_row_new'] && count($updateData['entity_row_new'])) {
            $updateData['entity_row_new']['entity_id'] = $rowData['_entity_id'];
            $defaults = [];
            foreach (self::getDefaultAddressAttributeMapping() as $columnName => $attributeCode) {
                if (!empty($rowData[$columnName]) && $rowData[self::COLUMN_ADDRESS_ID]) {
                    $email = strtolower($rowData[self::COLUMN_EMAIL]);
                    $customerId = $this->_getCustomerId($email, $rowData[self::COLUMN_WEBSITE]);
                    $table = $this->_getCustomerEntity()->getResource()->getTable('customer_entity');
                    $defaults[$table][$customerId][$attributeCode] = $rowData[self::COLUMN_ADDRESS_ID];
                }
            }
            if (!empty($defaults)) {
                $updateData['defaults'] = $defaults;
            }
        }
        return $updateData;
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
        $this->_initCustomerAddressEntity();
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
                    if (\method_exists($this, 'prepareCustomerData')) {
                        $this->prepareCustomerData([$rowData]);
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
                    $this->validateCustomerAddressEntity($rowData, $source->key());
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
     * Init customer address entity
     */
    protected function _initCustomerAddressEntity()
    {
        try {
            $table = $this->_connection->getTableName('customer_address_entity');
            $select = $this->_connection->select()->from($table, ['entity_id', 'parent_id']);
            $result = $this->_connection->fetchAll($select);
            if ($result) {
                foreach ($result as $res) {
                    $this->comparableList[$res['entity_id']] = $res['parent_id'];
                }
            }
        } catch (Exception $e) {
            $this->addLogWriteln($e->getMessage(), $this->output, 'error');
        }
    }

    /**
     * @param $entityIdDump
     * @param $customerId
     * @return bool
     */
    protected function isEntityAddressIdAtAnotherCustomer($entityIdDump, $customerId)
    {
        $result = false;
        if (isset($this->comparableList[$entityIdDump])
            && $this->comparableList[$entityIdDump] != $customerId
        ) {
            $result = true;
        }
        return $result;
    }

    /**
     * @param $rowData
     * @param $rowNum
     */
    protected function validateCustomerAddressEntity($rowData, $rowNum)
    {
        $email = $rowData[self::COLUMN_EMAIL] ?? '';
        $website = $rowData[self::COLUMN_WEBSITE] ?? '';
        $entityId = $rowData['_entity_id'] ?? '';
        if (!empty($email) && !empty($website)) {
            $customerId = $this->_getCustomerId($email, $website);
            if ($customerId
                && $this->isEntityAddressIdAtAnotherCustomer($entityId, $customerId)
            ) {
                $message = 'this _entity_id = %1 belongs to another customer';
                $this->addLogWriteln(__($message, $entityId, $this->output, 'error'));
                $this->addRowError(__($message, $entityId), $rowNum);
            }
        }
    }
}
