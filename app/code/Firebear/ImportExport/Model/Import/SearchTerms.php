<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import;

use Exception;
use Firebear\ImportExport\Traits\Import\Entity as ImportTrait;
use Firebear\ImportExport\Api\Data\ImportMappingInterface;
use InvalidArgumentException;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\ImportFactory;
use Magento\ImportExport\Model\Import\AbstractEntity;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Magento\ImportExport\Model\ResourceModel\Helper;
use Magento\ImportExport\Helper\Data as ImportExportData;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime;
use Magento\Search\Model\Query;
use Magento\Search\Model\ResourceModel\Query\CollectionFactory;
use Magento\Search\Model\ResourceModel\Query as QueryResource;
use Magento\Store\Api\StoreRepositoryInterface;

/**
 * Class SearchTerms
 *
 * @package Firebear\ImportExport\Model\Import
 */
class SearchTerms extends AbstractEntity implements ImportAdapterInterface
{
    use ImportTrait;

    /**
     * Entity type code
     */
    const ENTITY_TYPE_CODE = 'search_query';

    /**
     * Search Query fields
     */
    const COL_QUERY_ID      = 'query_id';
    const COL_QUERY_TEXT    = 'query_text';
    const COL_STORE_ID      = 'store_id';
    const COL_UPDATED_AT    = 'updated_at';
    /** @ */

    /**
     * List of available behaviors
     *
     * @var string[]
     */
    protected $_availableBehaviors = [
        Import::BEHAVIOR_APPEND,
        Import::BEHAVIOR_REPLACE,
        Import::BEHAVIOR_DELETE,
    ];

    /**
     * Json Serializer
     *
     * @var Json
     */
    protected $json;

    /**
     * Import export data
     *
     * @var ImportExportData
     */
    protected $_importExportData;

    /**
     * Source model
     *
     * @var Helper
     */
    protected $_resourceHelper;

    /**
     * Collection Factory
     *
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var QueryResource
     */
    protected $queryResource;

    /**
     * @var StoreRepositoryInterface
     */
    protected $storeRepository;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * Search Query Text and Store Id to ID hash.
     *
     * @var array
     */
    protected $searchQueries = [];

    /**
     * @var array
     */
    protected $searchQueriesForReplace = [];

    /**
     * @var array
     */
    private $entityFieldsToUpdate = [];

    /**
     * Next Entity Id
     *
     * @var int
     */
    private $nextEntityId;

    /**
     * @var array
     */
    protected $storeList = [];

    /**
     * Search Terms constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param ImportFactory $importFactory
     * @param Json $json
     * @param CollectionFactory $collectionFactory
     * @param StoreRepositoryInterface $storeRepository
     * @param QueryResource $queryResource
     * @param DateTime $dateTime
     * @param array $data
     * @throws NoSuchEntityException
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        ImportFactory $importFactory,
        Json $json,
        CollectionFactory $collectionFactory,
        StoreRepositoryInterface $storeRepository,
        QueryResource $queryResource,
        DateTime $dateTime,
        array $data = []
    ) {
        parent::__construct(
            $context->getStringUtils(),
            $scopeConfig,
            $importFactory,
            $context->getResourceHelper(),
            $context->getResource(),
            $context->getErrorAggregator(),
            $data
        );

        $this->collectionFactory = $collectionFactory;
        $this->storeRepository = $storeRepository;
        $this->_importExportData = $context->getImportExportData();
        $this->_resourceHelper = $context->getResourceHelper();
        $this->_dataSourceModel = $context->getDataSourceModel();
        $this->queryResource = $queryResource;
        $this->dateTime = $dateTime;
        $this->json = $json;
        $this->output = $context->getOutput();
        $this->_logger = $context->getLogger();

        $this->initSearchQueries();
        $this->initStore();
    }

    /**
     * Imported entity type code getter.
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        return self::ENTITY_TYPE_CODE;
    }

    /**
     * Initialize Search Queries Data
     *
     * @return void
     * @throws NoSuchEntityException
     */
    private function initSearchQueries()
    {
        if (empty($this->searchQueries)) {
            $collection = $this->collectionFactory->create();
            /** @var Query $searchQuery */
            foreach ($collection as $searchQuery) {
                $queryText = $searchQuery->getQueryText();
                $storeId = $searchQuery->getStoreId();
                $this->searchQueries[$queryText][$storeId] = $searchQuery->getId();
            }
        }
    }

    /**
     * Initialize Store id data
     */
    protected function initStore()
    {
        $stores = $this->storeRepository->getList();
        foreach ($stores as $store) {
            $this->storeList[$store->getId()] = 1;
        }
    }

    /**
     * Import data rows.
     *
     * @return boolean
     * @throws LocalizedException
     */
    protected function _importData()
    {
        $this->_validatedRows = null;
        if (Import::BEHAVIOR_DELETE == $this->getBehavior()) {
            $this->delete();
        } elseif (Import::BEHAVIOR_REPLACE == $this->getBehavior()) {
            $this->replaceProcess();
        } else {
            $this->save();
        }

        return true;
    }

    /**
     * @return $this
     * @throws LocalizedException
     */
    protected function replaceProcess()
    {
        $this->delete();
        if (!empty($this->searchQueriesForReplace)) {
            $this->save();
        }

        return $this;
    }

    /**
     * Delete Search Queries if delete behaviour is selected
     *
     * @return $this
     */
    protected function delete()
    {
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            foreach ($bunch as $rowNum => $rowData) {
                if ($this->validateRow($rowData, $rowNum)) {
                    $idToDelete = $this->getProcessedId($rowData);
                    if ($idToDelete) {
                        try {
                            $del = $this->_connection->delete(
                                $this->queryResource->getMainTable(),
                                [self::COL_QUERY_ID . ' IN (?)' => $idToDelete]
                            );
                            if ($del) {
                                $this->countItemsDeleted++;
                                if (Import::BEHAVIOR_REPLACE == $this->getBehavior()) {
                                    $this->searchQueriesForReplace[$idToDelete] = true;
                                }
                            }
                        } catch (Exception $e) {
                            $this->addLogWriteln(__('Search Term can\'t be deleted.'), $this->output, 'error');
                        }
                    }
                }
            }
        }

        if ($count = $this->getDeletedItemsCount()) {
            $this->addLogWriteln(__('Deleted: %1 Search Terms.', $count), $this->output, 'info');
        }

        return $this;
    }

    /**
     * Gather and save information of Search Query entities
     *
     * @return $this
     * @throws LocalizedException
     */
    protected function save()
    {
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $rowsToUpdate = [];
            $rowsToInsert = [];
            foreach ($bunch as $rowNum => $rowData) {
                $this->_processedRowsCount++;
                $rowData = $this->joinIdenticalyData($rowData);
                $rowData = $this->customChangeData($rowData);
                $rowData = $this->prepareRowData($rowData);
                if (!$this->validateRow($rowData, $rowNum)) {
                    continue;
                }

                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                    continue;
                }

                if ($rowData) {
                    if (!empty($rowData[self::COL_QUERY_ID])) {
                        $rowsToUpdate[] = $rowData;
                    } else {
                        $rowData[self::COL_QUERY_ID] = $this->getNextEntityId();
                        $rowsToInsert[] = $rowData;
                    }

                    $rowQueryText = $rowData[self::COL_QUERY_TEXT];
                    $rowStoreId = $rowData[self::COL_STORE_ID] ?? 0;
                    $this->searchQueries[$rowQueryText][$rowStoreId] = $rowData[self::COL_QUERY_ID];
                }
            }

            try {
                if (Import::BEHAVIOR_REPLACE == $this->getBehavior() &&
                    !empty($rowsToUpdate) &&
                    !empty($this->searchQueriesForReplace)
                ) {
                    $rowsToUpdatePrepare = [];
                    foreach ($rowsToUpdate as $rowData) {
                        if (isset($this->searchQueriesForReplace[$rowData[self::COL_QUERY_ID]])) {
                            $rowsToUpdatePrepare[] = $rowData;
                        }
                    }
                    $rowsToUpdate = $rowsToUpdatePrepare;
                } else {
                    if (!empty($rowsToInsert)) {
                        $this->countItemsCreated += $this->_connection->insertMultiple(
                            $this->queryResource->getMainTable(),
                            $rowsToInsert
                        );
                    }
                }
                if (!empty($rowsToUpdate)) {
                    $this->countItemsUpdated += $this->_connection->insertOnDuplicate(
                        $this->queryResource->getMainTable(),
                        $rowsToUpdate,
                        $this->getEntityFieldsToUpdate(reset($rowsToUpdate))
                    );
                }
            } catch (Exception $e) {
                $this->getErrorAggregator()->addError(
                    $e->getCode(),
                    ProcessingError::ERROR_LEVEL_NOT_CRITICAL,
                    $this->getProcessedRowsCount(),
                    null,
                    $e->getMessage()
                );
            }
        }

        if ($this->getCreatedItemsCount()) {
            $this->addLogWriteln(__('Imported: %1 Search Terms.', $this->getCreatedItemsCount()), $this->output);
        }
        if ($this->getUpdatedItemsCount()) {
            $this->addLogWriteln(__('Updated: %1 Search Terms.', $this->getUpdatedItemsCount()), $this->output);
        }

        return $this;
    }

    /**
     * Filter the entity that are being updated so we only change fields found in the importer file
     *
     * @param array $rowData
     * @return array
     * @throws LocalizedException
     */
    private function getEntityFieldsToUpdate(array $rowData)
    {
        if (empty($this->entityFieldsToUpdate)) {
            $columnsToUpdate = array_keys($rowData);
            $this->entityFieldsToUpdate = array_filter(
                $this->getAllFields(),
                function ($field) use ($columnsToUpdate) {
                    return in_array($field, $columnsToUpdate);
                }
            );
        }
        return $this->entityFieldsToUpdate;
    }

    /**
     * Prepare row data for update/replace behaviour
     *
     * @param array $rowData
     * @return array
     */
    public function prepareRowData(array $rowData)
    {
        if (isset($rowData[self::COL_STORE_ID])) {
            try {
                $storeId = $this->storeRepository->getById($rowData[self::COL_STORE_ID])->getId();
                $rowData[self::COL_STORE_ID] = ($storeId) ? $storeId : 0;
            } catch (NoSuchEntityException $e) {
                $rowData[self::COL_STORE_ID] = 0;
            }
        }

        if (isset($rowData[self::COL_QUERY_TEXT])) {
            $rowQueryText = $rowData[self::COL_QUERY_TEXT];
            $rowStoreId = $rowData[self::COL_STORE_ID] ?? 0;
            if (!empty($this->searchQueries[$rowQueryText][$rowStoreId])) {
                $rowData[self::COL_QUERY_ID] = $this->searchQueries[$rowQueryText][$rowStoreId];
            }
        }

        if (isset($rowData[self::COL_UPDATED_AT])) {
            $rowData[self::COL_UPDATED_AT] = $this->dateTime->formatDate($rowData[self::COL_UPDATED_AT], true);
        }

        return $rowData;
    }

    /**
     * Retrieve Query id's for delete
     *
     * @param array $rowData
     * @return mixed
     */
    public function getProcessedId(array $rowData)
    {
        $processedId = false;

        if (isset($rowData[self::COL_QUERY_ID])) {
            $processedId = $rowData[self::COL_QUERY_ID];
        } elseif (isset($rowData[self::COL_QUERY_TEXT])
            && isset($rowData[self::COL_STORE_ID])
            && isset($this->searchQueries[$rowData[self::COL_QUERY_TEXT]][$rowData[self::COL_STORE_ID]])
        ) {
            $processedId = $this->searchQueries[$rowData[self::COL_QUERY_TEXT]][$rowData[self::COL_STORE_ID]];
        } elseif (isset($rowData[self::COL_QUERY_TEXT])
            && isset($this->searchQueries[$rowData[self::COL_QUERY_TEXT]])
        ) {
            foreach ($this->searchQueries[$rowData[self::COL_QUERY_TEXT]] as $storeId => $id) {
                $processedId = $id;
            }
        }

        return $processedId;
    }

    /**
     * Retrieve All Fields Source (the column descriptions for a table)
     *
     * @return array
     * @throws LocalizedException
     */
    public function getAllFields()
    {
        $fields = $this->_connection->describeTable($this->queryResource->getMainTable());
        return array_keys($fields);
    }

    /**
     * Retrieve Next Entity Id
     *
     * @return int
     * @throws LocalizedException
     */
    public function getNextEntityId()
    {
        if (!$this->nextEntityId) {
            $this->nextEntityId = $this->_resourceHelper->getNextAutoincrement($this->queryResource->getMainTable());
        }
        return $this->nextEntityId++;
    }

    /**
     * Validate data row.
     *
     * @param array $rowData
     * @param int $rowNum
     * @return boolean
     */
    public function validateRow(array $rowData, $rowNum)
    {
        if (isset($this->_validatedRows[$rowNum])) {
            // check that row is already validated
            return !$this->getErrorAggregator()->isRowInvalid($rowNum);
        }

        $this->_validatedRows[$rowNum] = true;
        $this->_processedEntitiesCount++;

        /* behavior selector */
        switch ($this->getBehavior()) {
            case Import::BEHAVIOR_DELETE:
            case Import::BEHAVIOR_REPLACE:
            case Import::BEHAVIOR_APPEND:
                if (empty($rowData)) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                } elseif (empty($rowData[self::COL_QUERY_TEXT])) {
                    $eMessage = __('Column QUERY_TEXT is empty. Row:#%1', $rowNum);
                    $this->addLogWriteln($eMessage, $this->output, 'error');
                    $this->addRowError($eMessage, $rowNum);
                }
                break;
        }

        return !$this->getErrorAggregator()->isRowInvalid($rowNum);
    }

    /**
     * Save Validated Bunches
     *
     * @return $this
     * @throws LocalizedException
     */
    protected function _saveValidatedBunches()
    {
        $source = $this->getSource();
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
                $currentDataSize = strlen($this->phpSerialize($bunchRows));
                $startNewBunch = false;
                $nextRowBackup = [];
            }

            if ($source->valid()) {
                try {
                    $rowData = $source->current();
                } catch (InvalidArgumentException $e) {
                    $this->addRowError($e->getMessage(), $this->getProcessedRowsCount());
                    $this->_processedRowsCount++;
                    $source->next();
                    continue;
                }
                $rowData = $this->customFieldsMapping($rowData);
                $rowData = $this->customBunchesData($rowData);
                $this->_processedRowsCount++;
                if ($this->checkStoreId($rowData, $source->key())) {

                    $rowSize = strlen($this->json->serialize($rowData));

                    $isBunchSizeExceeded = $bunchSize > 0 && count($bunchRows) >= $bunchSize;

                    if ($currentDataSize + $rowSize >= $maxDataSize || $isBunchSizeExceeded) {
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
     * @param $rowData
     * @param $rowNum
     * @return bool
     */
    public function checkStoreId($rowData, $rowNum)
    {
        if (!isset($this->storeList[$rowData['store_id']])) {
            $eMessage = __('Column store is no exist. Row:#%1', $rowNum);
            $this->addLogWriteln($eMessage, $this->output, 'error');
            $this->addRowError($eMessage, $rowNum);
            return false;
        }

        return true;
    }

    /**
     * @param $rowData
     * @return mixed
     */
    public function customFieldsMapping($rowData)
    {
        if (isset($this->_parameters['map']) && isset($this->_parameters['replace_default_value'])) {
            foreach ($this->_parameters['map'] as $field) {
                if ($field['system'] !== $field['import']) {
                    $defaultValueMapping = $this->getDefaultValueMapping(
                        $this->_parameters['job_id'],
                        $field['import']
                    );
                    if ($defaultValueMapping) {
                        if ($this->_parameters['replace_default_value'] == 1) {
                            $rowData[$field['system']] = $defaultValueMapping;
                        }
                    }
                    unset($rowData[$field['import']]);
                }
            }
        }

        return $rowData;
    }

    /**
     * @param $jobId
     * @param $importCode
     * @return string
     */
    protected function getDefaultValueMapping($jobId, $importCode)
    {
        $table = $this->_connection->getTableName('firebear_import_job_mapping');
        $select = $this->_connection->select();
        $select->from($table, ImportMappingInterface::DEFAULT_VALUE)
            ->where(ImportMappingInterface::IMPORT_CODE.' = ?', $importCode)
            ->where(ImportMappingInterface::JOB_ID.' = ?', $jobId);
        $result = $this->_connection->fetchOne($select);

        return $result ? $result : '';
    }
}
