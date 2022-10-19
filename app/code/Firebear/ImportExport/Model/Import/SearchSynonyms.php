<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import;

use Exception;
use Firebear\ImportExport\Model\Export\SearchSynonyms\SynonymsInterface;
use Firebear\ImportExport\Api\Data\ImportMappingInterface;
use Firebear\ImportExport\Traits\Import\Entity as ImportTrait;
use InvalidArgumentException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\ImportExport\Helper\Data as ImportExportData;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\AbstractEntity;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Magento\ImportExport\Model\ImportFactory;
use Magento\ImportExport\Model\ResourceModel\Helper;
use Magento\Search\Model\ResourceModel\SynonymGroup\CollectionFactory;
use Magento\Search\Model\ResourceModel\SynonymGroup as ResourceModel;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;

/**
 * Class SearchSynonyms
 *
 * @package Firebear\ImportExport\Model\Import
 */
class SearchSynonyms extends AbstractEntity implements ImportAdapterInterface
{
    use ImportTrait;

    /**
     * Entity type code
     */
    const ENTITY_TYPE_CODE = 'search_synonyms';

    /**
     * List of available behaviors
     *
     * @var string[]
     */
    protected $_availableBehaviors = [
        Import::BEHAVIOR_ADD_UPDATE,
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
     * @var Helper
     */
    protected $_resourceHelper;

    /**
     * @var StoreRepositoryInterface
     */
    protected $storeRepository;

    /**
     * @var WebsiteRepositoryInterface
     */
    protected $websiteRepository;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ResourceModel
     */
    private $resourceModel;

    /**
     * @var array
     */
    private $synonymsGroup = [];

    /**
     * @var array
     */
    private $synonymsGroupIds = [];

    /**
     * @var array
     */
    private $groupIdForReplaceNeedAdd = [];

    /**
     * @var array
     */
    private $synonymsFields = [
        SynonymsInterface::GROUP_ID,
        SynonymsInterface::SYNONYMS,
        SynonymsInterface::WEBSITE_ID,
        SynonymsInterface::STORE_ID,
    ];

    /**
     * Error codes
     */
    const ERROR_IDENTIFIERS_IS_EMPTY = 'groupIdAndSynonymsIsEmpty';

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_messageTemplates = [
        self::ERROR_IDENTIFIERS_IS_EMPTY => 'Column GROUP_ID and SYNONYMS is empty.',
    ];

    /**
     * Next Entity Id
     *
     * @var int
     */
    private $nextEntityId;

    /**
     * SearchSynonyms constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param ImportFactory $importFactory
     * @param Json $json
     * @param StoreRepositoryInterface $storeRepository
     * @param WebsiteRepositoryInterface $websiteRepository
     * @param CollectionFactory $collectionFactory
     * @param ResourceModel $resourceModel
     * @param array $data
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        ImportFactory $importFactory,
        Json $json,
        StoreRepositoryInterface $storeRepository,
        WebsiteRepositoryInterface $websiteRepository,
        CollectionFactory $collectionFactory,
        ResourceModel $resourceModel,
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
        $this->resourceModel = $resourceModel;
        $this->collectionFactory = $collectionFactory;
        $this->storeRepository = $storeRepository;
        $this->websiteRepository = $websiteRepository;
        $this->_importExportData = $context->getImportExportData();
        $this->_resourceHelper = $context->getResourceHelper();
        $this->_dataSourceModel = $context->getDataSourceModel();
        $this->json = $json;
        $this->output = $context->getOutput();
        $this->_logger = $context->getLogger();

        foreach ($this->_messageTemplates as $errorCode => $message) {
            $this->getErrorAggregator()->addErrorMessageTemplate($errorCode, $message);
        }
        $this->initSynonymsGroup();
    }

    /**
     * Initialize Search Synonyms
     */
    private function initSynonymsGroup()
    {
        if (empty($this->synonymsGroup)) {
            $synonyms = $this->collectionFactory->create();
            /** @var SynonymsInterface $synonym */
            foreach ($synonyms as $synonym) {
                $groupId = $synonym->getGroupId();
                $storeId = $synonym->getStoreId();
                $webSiteId = $synonym->getWebsiteId();
                $expSynonyms = explode(',', $synonym->getSynonymGroup());
                $this->synonymsGroup[$webSiteId][$storeId][$groupId] = $expSynonyms;
                $this->synonymsGroupIds[] = $groupId;
            }
        }
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
     * Retrieve All Fields Source
     *
     * @return array
     */
    public function getAllFields()
    {
        return array_unique($this->synonymsFields);
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
            $this->nextEntityId = $this->_resourceHelper->getNextAutoincrement(
                $this->resourceModel->getMainTable()
            );
        }
        return $this->nextEntityId++;
    }

    /**
     * Import data rows
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
     * start this process if checked behavior replace
     *
     * @return $this
     * @throws LocalizedException
     */
    protected function replaceProcess()
    {
        if (empty($this->synonymsGroup)) {
            $this->addLogWriteln(
                __('Search synonyms can\'t be replaced. Firstly add before replace'),
                $this->output,
                'error'
            );
        } else {
            $this->delete();
            $this->save();
        }

        return $this;
    }

    /**
     * Delete Search Synonyms if delete behaviour is selected
     *
     * @return $this
     */
    private function delete()
    {
        $idsToDelete = [];
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            foreach ($bunch as $rowNum => $rowData) {
                /* validate data */
                if (!$rowData || !$this->validateRow($rowData, $rowNum)) {
                    continue;
                }

                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                    continue;
                }

                if (Import::BEHAVIOR_REPLACE == $this->getBehavior()) {
                    if (isset($rowData[SynonymsInterface::GROUP_ID])
                        && $this->isSearchSynonyms($rowData)) {
                        $idsToDelete[] = $rowData[SynonymsInterface::GROUP_ID];
                    }
                } else {
                    $rowData = $this->prepareRowForDelete($rowData);
                    $idsToDelete = array_merge($idsToDelete, $rowData[SynonymsInterface::GROUP_ID]);
                }
            }
        }
        if ($idsToDelete) {
            try {
                $this->countItemsDeleted += $this->_connection->delete(
                    $this->resourceModel->getMainTable(),
                    [SynonymsInterface::GROUP_ID . ' IN (?)' => $idsToDelete]
                );
                if (Import::BEHAVIOR_REPLACE == $this->getBehavior()) {
                    $this->groupIdForReplaceNeedAdd = $idsToDelete;
                }
                $this->addLogWriteln(
                    __('Deleted: %1 Synonym Groups.', $this->getDeletedItemsCount()),
                    $this->output,
                    'info'
                );
            } catch (Exception $e) {
                $this->addLogWriteln(__('Synonym Groups can\'t be deleted.'), $this->output, 'error');
            }
        }

        return $this;
    }

    /**
     * Check isset group_id from Search Synonyms Ids
     *
     * @param array $rowData
     * @return bool
     */
    private function isSearchSynonyms(array $rowData)
    {
        $result = false;
        if (in_array($rowData[SynonymsInterface::GROUP_ID], $this->synonymsGroupIds)) {
            $result = true;
        }

        return $result;
    }

    /**
     * Gather and save information of Search Synonyms
     *
     * @return $this
     * @throws LocalizedException
     */
    public function save()
    {
        $idsToDelete = [];
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $rowsToUpdate = [];
            $rowsToInsert = [];
            foreach ($bunch as $rowNum => $rowData) {
                $this->_processedRowsCount++;
                $rowData = $this->joinIdenticalyData($rowData);
                $rowData = $this->customChangeData($rowData);

                if (Import::BEHAVIOR_REPLACE == $this->getBehavior()
                    && !$this->isDataForReplace($rowData)) {
                    continue;
                }

                $rowData = $this->prepareRowData($rowData);
                if (!$this->validateRow($rowData, $rowNum)) {
                    continue;
                }

                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                    continue;
                }

                if ($rowData) {
                    if (!empty($rowData[SynonymsInterface::GROUP_ID])) {
                        $idToUpdate = null;
                        $idToUpdate = array_shift($rowData[SynonymsInterface::GROUP_ID]);
                        $idsToDelete = array_merge($idsToDelete, $rowData[SynonymsInterface::GROUP_ID]);

                        foreach ($this->synonymsGroup as $websiteId => $websiteSynonyms) {
                            foreach ($websiteSynonyms as $storeId => $storeSynonyms) {
                                foreach ($storeSynonyms as $groupId => $synonyms) {
                                    if (in_array($groupId, $idsToDelete)) {
                                        unset($this->synonymsGroup[$websiteId][$storeId][$groupId]);
                                    }
                                }
                            }
                        }

                        $rowData[SynonymsInterface::GROUP_ID] = $idToUpdate;
                    }

                    if ($rowData[SynonymsInterface::GROUP_ID]) {
                        $rowsToUpdate[] = $rowData;
                    } else {
                        $rowData[SynonymsInterface::GROUP_ID] = $this->getNextEntityId();
                        $rowsToInsert[] = $rowData;
                    }

                    $websiteId = $rowData[SynonymsInterface::WEBSITE_ID] ?? 0;
                    $storeId = $rowData[SynonymsInterface::STORE_ID] ?? 0;
                    $groupId = $rowData[SynonymsInterface::GROUP_ID];
                    $expRowSynonyms = explode(',', $rowData[SynonymsInterface::SYNONYMS]);
                    $this->synonymsGroup[$websiteId][$storeId][$groupId] = $expRowSynonyms;
                }
            }

            try {
                if (!empty($rowsToInsert)) {
                    $this->countItemsCreated += $this->_connection->insertMultiple(
                        $this->resourceModel->getMainTable(),
                        $rowsToInsert
                    );
                }

                if (!empty($rowsToUpdate)) {
                    $this->countItemsUpdated += $this->_connection->insertOnDuplicate(
                        $this->resourceModel->getMainTable(),
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

        try {
            if (!empty($idsToDelete)) {
                $this->countItemsDeleted += $this->_connection->delete(
                    $this->resourceModel->getMainTable(),
                    [SynonymsInterface::GROUP_ID . ' IN (?)' => $idsToDelete]
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

        if ($this->getDeletedItemsCount()) {
            $this->addLogWriteln(__('Deleted: %1 Synonym Groups.', $this->getDeletedItemsCount()), $this->output);
        }
        if ($this->getCreatedItemsCount()) {
            $this->addLogWriteln(__('Imported: %1 Synonym Groups.', $this->getCreatedItemsCount()), $this->output);
        }
        if ($this->getUpdatedItemsCount()) {
            $this->addLogWriteln(__('Updated: %1 Synonym Groups.', $this->getUpdatedItemsCount()), $this->output);
        }

        return $this;
    }

    /**
     * Validate data row
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return bool
     */
    public function validateRow(array $rowData, $rowNumber)
    {
        if (isset($this->_validatedRows[$rowNumber])) {
            /* check that row is already validated */
            return !$this->getErrorAggregator()->isRowInvalid($rowNumber);
        }

        $this->_validatedRows[$rowNumber] = true;
        $this->_processedEntitiesCount++;

        /* behavior selector */
        switch ($this->getBehavior()) {
            case Import::BEHAVIOR_DELETE:
                break;
            case Import::BEHAVIOR_REPLACE:
            case Import::BEHAVIOR_ADD_UPDATE:
                $this->validateRowForUpdate($rowData, $rowNumber);
                break;
        }
        return !$this->getErrorAggregator()->isRowInvalid($rowNumber);
    }

    /**
     * Validate row data for update behaviour
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return void
     */
    public function validateRowForUpdate(array $rowData, $rowNumber)
    {
        if (empty($rowData[SynonymsInterface::GROUP_ID]) && empty($rowData[SynonymsInterface::SYNONYMS])) {
            $eMessage = $this->_messageTemplates[self::ERROR_IDENTIFIERS_IS_EMPTY];
            $this->addLogWriteln(
                __($eMessage) . " " . __('Row:#%1', $rowNumber),
                $this->output,
                'error'
            );
            $this->addRowError(self::ERROR_IDENTIFIERS_IS_EMPTY, $rowNumber);
        }
    }

    /**
     * Retrieve Synonyms Group id's for delete
     *
     * @param array $rowData
     * @return array
     */
    private function prepareRowForDelete(array $rowData)
    {
        $processedIds = [];
        $rowSynonyms = $rowData[SynonymsInterface::SYNONYMS] ?? null;
        $websiteId = $rowData[SynonymsInterface::WEBSITE_ID] ?? null;
        $storeId = $rowData[SynonymsInterface::STORE_ID] ?? null;
        $groupId = $rowData[SynonymsInterface::GROUP_ID] ?? null;

        if ($rowSynonyms && $websiteId && $storeId && !empty($this->synonymsGroup[$websiteId][$storeId])) {
            foreach ($this->synonymsGroup[$websiteId][$storeId] as $groupId => $synonyms) {
                $expRowSynonyms = explode(',', $rowSynonyms);
                if (empty(array_diff($expRowSynonyms, $synonyms))
                    && empty(array_diff($synonyms, $expRowSynonyms))
                ) {
                    $processedIds[] = $groupId;
                }
            }
        } elseif ($rowSynonyms && $websiteId && !empty($this->synonymsGroup[$websiteId])) {
            foreach ($this->synonymsGroup[$websiteId] as $storeSynonyms) {
                foreach ($storeSynonyms as $groupId => $synonyms) {
                    $expRowSynonyms = explode(',', $rowSynonyms);
                    if (empty(array_diff($expRowSynonyms, $synonyms))
                        && empty(array_diff($synonyms, $expRowSynonyms))
                    ) {
                        $processedIds[] = $groupId;
                    }
                }
            }
        } elseif ($rowSynonyms && !empty($this->synonymsGroup)) {
            foreach ($this->synonymsGroup as $websiteSynonyms) {
                foreach ($websiteSynonyms as $storeSynonyms) {
                    foreach ($storeSynonyms as $groupId => $synonyms) {
                        $expRowSynonyms = explode(',', $rowSynonyms);
                        if (empty(array_diff($expRowSynonyms, $synonyms))
                            && empty(array_diff($synonyms, $expRowSynonyms))
                        ) {
                            $processedIds[] = $groupId;
                        }
                    }
                }
            }
        } elseif ($groupId) {
            $processedIds[] = $groupId;
        }

        $rowData[SynonymsInterface::GROUP_ID] = $processedIds;

        return $rowData;
    }

    /**
     * Prepare row data for update/replace behaviour
     *
     * @param array $rowData
     * @return array
     */
    private function prepareRowData(array $rowData)
    {
        if (!empty($rowData[SynonymsInterface::SYNONYMS])) {
            $expRowSynonyms = explode(',', $rowData[SynonymsInterface::SYNONYMS]);
            $websiteId = $rowData[SynonymsInterface::WEBSITE_ID] ?? null;
            $storeId = $rowData[SynonymsInterface::STORE_ID] ?? null;
            $matchingSynonymGroups = [];
            $processedIds = [];

            if ($websiteId && $storeId && !empty($this->synonymsGroup[$websiteId][$storeId])) {
                foreach ($this->synonymsGroup[$websiteId][$storeId] as $groupId => $synonyms) {
                    if (array_intersect($expRowSynonyms, $synonyms)) {
                        $matchingSynonymGroups[$groupId] = $synonyms;
                    }
                }
            } elseif ($websiteId && !empty($this->synonymsGroup[$websiteId])) {
                foreach ($this->synonymsGroup[$websiteId] as $storeSynonyms) {
                    foreach ($storeSynonyms as $groupId => $synonyms) {
                        if (array_intersect($expRowSynonyms, $synonyms)) {
                            $matchingSynonymGroups[$groupId] = $synonyms;
                        }
                    }
                }
            } elseif (!empty($this->synonymsGroup)) {
                foreach ($this->synonymsGroup as $websiteSynonyms) {
                    foreach ($websiteSynonyms as $storeSynonyms) {
                        foreach ($storeSynonyms as $groupId => $synonyms) {
                            if (array_intersect($expRowSynonyms, $synonyms)) {
                                $matchingSynonymGroups[$groupId] = $synonyms;
                            }
                        }
                    }
                }
            }

            if ($matchingSynonymGroups) {
                foreach ($matchingSynonymGroups as $groupId => $matchingSynonyms) {
                    if (Import::BEHAVIOR_REPLACE != $this->getBehavior()) {
                        $expRowSynonyms = array_merge($expRowSynonyms, $matchingSynonyms);
                    }

                    $processedIds[] = $groupId;
                }
            }

            $rowData[SynonymsInterface::SYNONYMS] = implode(',', array_unique($expRowSynonyms));
        }

        if (empty($rowData[SynonymsInterface::GROUP_ID])) {
            $rowData[SynonymsInterface::GROUP_ID] = null;
        } elseif (!is_array($rowData[SynonymsInterface::GROUP_ID])) {
            $rowData[SynonymsInterface::GROUP_ID] = [$rowData[SynonymsInterface::GROUP_ID]];
        }

        if (isset($rowData[SynonymsInterface::WEBSITE_ID])) {
            try {
                $websiteId = $this->storeRepository->getById($rowData[SynonymsInterface::WEBSITE_ID])->getId();
                $rowData[SynonymsInterface::WEBSITE_ID] = ($websiteId) ? $websiteId : 0;
            } catch (NoSuchEntityException $e) {
                $rowData[SynonymsInterface::WEBSITE_ID] = 0;
            }
        }

        if (isset($rowData[SynonymsInterface::STORE_ID])) {
            try {
                $storeId = $this->storeRepository->getById($rowData[SynonymsInterface::STORE_ID])->getId();
                $rowData[SynonymsInterface::STORE_ID] = ($storeId) ? $storeId : 0;
            } catch (NoSuchEntityException $e) {
                $rowData[SynonymsInterface::STORE_ID] = 0;
            }
        }

        return $rowData;
    }

    /**
     * Check data for behavior replace
     *
     * @param array $rowData
     * @return bool
     */
    private function isDataForReplace(array $rowData)
    {
        $result = false;
        if (in_array($rowData[SynonymsInterface::GROUP_ID], $this->groupIdForReplaceNeedAdd)) {
            $result = true;
        }

        return $result;
    }

    /**
     * Filter the entity that are being updated so we only change fields found in the importer file
     *
     * @param array $rowData
     * @return array
     */
    private function getEntityFieldsToUpdate(array $rowData)
    {
        $columnsToUpdate = array_keys($rowData);
        $fieldsToUpdate = array_filter(
            $this->getAllFields(),
            function ($field) use ($columnsToUpdate) {
                return in_array($field, $columnsToUpdate);
            }
        );
        return $fieldsToUpdate;
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

                $rowSize = strlen($this->json->serialize($rowData));

                $isBunchSizeExceeded = $bunchSize > 0 && count($bunchRows) >= $bunchSize;

                if ($currentDataSize + $rowSize >= $maxDataSize || $isBunchSizeExceeded) {
                    $startNewBunch = true;
                    $nextRowBackup = [$source->key() => $rowData];
                } else {
                    $bunchRows[$source->key()] = $rowData;
                    $currentDataSize += $rowSize;
                }

                $source->next();
            }
        }
        return $this;
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
