<?php
namespace Firebear\ImportExport\Model\Import;

use Firebear\ImportExport\Api\Data\ImportMappingInterface;
use Firebear\ImportExport\Model\Job\Mapping;
use Firebear\ImportExport\Model\Job\MappingFactory;
use Magento\Framework\Exception\FileSystemException;
use Firebear\ImportExport\Api\Data\ImportInterface;
use Firebear\ImportExport\Traits\Import\Entity as ImportTrait;
use InvalidArgumentException;
use Zend_Validate_Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\AbstractEntity;
use Magento\ImportExport\Model\Import\AbstractSource;
use Magento\ImportExport\Model\ImportFactory;
use Firebear\ImportExport\Model\Source\Import\Behavior\Jobs as JobsBehavior;
use Firebear\ImportExport\Api\JobRepositoryInterface;
use Firebear\ImportExport\Model\Job\Converter as JobConverter;
use Firebear\ImportExport\Model\ResourceModel\Job\CollectionFactory as JobCollectionFactory;

/**
 * Class ImportJobs
 * @package Firebear\ImportExport\Model\Import
 */
class ImportJobs extends AbstractEntity implements ImportAdapterInterface
{
    use ImportTrait;

    /**
     * Entity Type Code
     */
    const ENTITY_TYPE_CODE = 'import_jobs';

    /**
     * @var ResourceConnection
     */
    protected $_resource;

    /**
     * @var array
     */
    private $_alreadyDeleted = [];

    /**
     * @var JobRepositoryInterface
     */
    protected $jobRepository;

    /**
     * @var JobConverter
     */
    protected $jobConverter;

    /**
     * @var JobCollectionFactory
     */
    protected $jobCollectionFactory;

    /**
     * @var MappingFactory
     */
    protected $mappingFactory;

    /**
     * @var array
     */
    protected $serializedColumns = [
        ImportInterface::BEHAVIOR_DATA,
        ImportInterface::SOURCE_DATA
    ];

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param ImportFactory $importFactory
     * @param JobRepositoryInterface $jobRepository
     * @param JobConverter $jobConverter
     * @param JobCollectionFactory $jobCollectionFactory
     * @param MappingFactory $mappingFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        ImportFactory $importFactory,
        JobRepositoryInterface $jobRepository,
        JobConverter $jobConverter,
        JobCollectionFactory $jobCollectionFactory,
        MappingFactory $mappingFactory,
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

        $this->output = $context->getOutput();
        $this->_resource = $context->getResource();
        $this->_importExportData = $context->getImportExportData();
        $this->_resourceHelper = $context->getResourceHelper();
        $this->jsonHelper = $context->getJsonHelper();
        $this->_dataSourceModel = $context->getDataSourceModel();
        $this->jobConverter = $jobConverter;
        $this->jobCollectionFactory = $jobCollectionFactory;
        $this->jobRepository = $jobRepository;
        $this->mappingFactory = $mappingFactory;
    }

    /**
     * Source model setter
     *
     * @param AbstractSource $source
     * @return $this
     */
    public function setSource(AbstractSource $source)
    {
        $this->_source = $source;
        $this->_dataValidated = false;

        return $this;
    }

    /**
     * Inner source object getter
     *
     * @return AbstractSource
     * @throws LocalizedException
     */
    protected function _getSource()
    {
        if (!$this->_source) {
            throw new LocalizedException(__('Please specify a source.'));
        }

        return $this->_source;
    }

    /**
     * Import Behavior Getter
     *
     * @param array $rowData
     * @return string
     */
    public function getBehavior(array $rowData = null)
    {
        if (isset($this->_parameters['behavior'])) {
            return $this->_parameters['behavior'];
        }

        return Import::getDefaultBehavior();
    }

    /**
     * Import Data Rows
     *
     * @return boolean
     * @throws FileSystemException
     * @throws LocalizedException
     * @throws Zend_Validate_Exception
     */
    protected function _importData()
    {
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            foreach ($bunch as $rowNumber => $rowData) {
                /* Validate data */
                if (!$rowData || !$this->validateRow($rowData, $rowNumber)) {
                    continue;
                }

                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNumber);
                    continue;
                }

                /* Behavior selector */
                switch ($this->getBehavior()) {
                    case Import::BEHAVIOR_DELETE:
                        $this->_deleteJob($rowData);
                        break;
                    case Import::BEHAVIOR_REPLACE:
                        $this->_saveJob(
                            $this->_prepareDataForReplace($rowData)
                        );
                        break;
                    case Import::BEHAVIOR_APPEND:
                        $this->_saveJob(
                            $this->_prepareDataForUpdate($rowData)
                        );
                        break;
                    case JobsBehavior::ONLY_ADD:
                        $this->_saveJob(
                            $this->_prepareDataForOnlyAdd($rowData)
                        );
                        break;
                    case JobsBehavior::ONLY_UPDATE:
                        $this->_saveJob(
                            $this->_prepareDataForOnlyUpdate($rowData)
                        );
                        break;
                }
            }
        }

        return true;
    }

    /**
     * Save Job
     *
     * @param array $rowData
     * @throws LocalizedException
     */
    protected function _saveJob(array $rowData)
    {
        if ($rowData) {
            $object = $this->jobConverter->getDataObjectByData($rowData);
            if (array_key_exists(ImportInterface::MAP, $rowData)) {
                $this->setMapDataIfDoesntExist($rowData, $object);
            }
            $this->unserializeFields($object);
            $this->jobRepository->save(
                $object
            );
        }
    }

    /**
     * Unserialize field in an object
     *
     * @param $object
     * @return void
     */
    private function unserializeFields($object)
    {
        foreach ($this->serializedColumns as $field) {
            $value = $object->getData($field);
            if ($value) {
                $object->setData($field, $this->jsonHelper->jsonDecode($value));
            }
        }
    }

    /**
     * Delete Import Job
     *
     * @param array $rowData
     * @throws LocalizedException
     */
    protected function _deleteJob(array $rowData)
    {
        $entityId = $rowData[ImportInterface::ENTITY_ID];
        if (!in_array($entityId, $this->_alreadyDeleted)) {
            $time = explode(" ", microtime());
            $startTime = $time[0] + $time[1];
            try {
                $this->jobRepository->deleteById($entityId);

                $time = explode(" ", microtime());
                $endTime = $time[0] + $time[1];
                $totalTime = $endTime - $startTime;
                $totalTime = round($totalTime, 5);
                $message = 'deleted job with entityId: %1 .... %2s';

                $this->addLogWriteln(__($message, $entityId, $totalTime), $this->output, 'info');
            } catch (\Exception $exception) {
                $message = 'Job with entityId %1 not exist';
                $this->addLogWriteln(__($message, $entityId), $this->output, 'info');
            }

            $this->_alreadyDeleted[] = $entityId;
        }
    }

    /**
     * Validate Data Row
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return boolean
     * @throws LocalizedException
     * @throws Zend_Validate_Exception
     */
    public function validateRow(array $rowData, $rowNumber)
    {
        if (isset($this->_validatedRows[$rowNumber])) {
            /* Check that row is already validated */
            return !$this->getErrorAggregator()->isRowInvalid($rowNumber);
        }

        $this->_validatedRows[$rowNumber] = true;
        $this->_processedEntitiesCount++;

        /* Behavior selector */
        switch ($this->getBehavior()) {
            case Import::BEHAVIOR_DELETE:
                $this->_validateRowForDelete($rowData, $rowNumber);
                break;
            case Import::BEHAVIOR_REPLACE:
                $this->_validateRowForReplace($rowData, $rowNumber);
                break;
            case Import::BEHAVIOR_ADD_UPDATE:
                $this->_validateRowForUpdate($rowData, $rowNumber);
                break;
            case JobsBehavior::ONLY_ADD:
                $this->_validateRowForOnlyAdd($rowData, $rowNumber);
                break;
            case JobsBehavior::ONLY_UPDATE:
                $this->_validateRowForOnlyUpdate($rowData, $rowNumber);
                break;
        }

        return !$this->getErrorAggregator()->isRowInvalid($rowNumber);
    }

    /**
     * Validate Row Data For Update Behaviour
     *
     * @param array $rowData
     * @param $rowNumber
     */
    protected function _validateRowForUpdate(array $rowData, $rowNumber)
    {
        if (!isset($rowData[ImportInterface::ENTITY_ID]) || empty($rowData[ImportInterface::ENTITY_ID])) {
            $this->addRowError('Invalid row %1', $rowNumber);
        }
    }

    /**
     * Validate Row Data For Only Add Behaviour
     *
     * @param array $rowData
     * @param $rowNumber
     */
    protected function _validateRowForOnlyAdd(array $rowData, $rowNumber)
    {
        if (!isset($rowData[ImportInterface::ENTITY_ID]) || empty($rowData[ImportInterface::ENTITY_ID])) {
            $this->addRowError('Invalid row %1', $rowNumber);
        }
    }

    /**
     * Validate Row Data For Replace Behaviour
     *
     * @param array $rowData
     * @param $rowNumber
     */
    protected function _validateRowForReplace(array $rowData, $rowNumber)
    {
        if (!isset($rowData[ImportInterface::ENTITY_ID]) || empty($rowData[ImportInterface::ENTITY_ID])) {
            $this->addRowError('Invalid row %1', $rowNumber);
        }
    }

    /**
     * Validate Row Data For Delete Behaviour
     *
     * @param array $rowData
     * @param $rowNumber
     */
    protected function _validateRowForDelete(array $rowData, $rowNumber)
    {
        if (!isset($rowData[ImportInterface::ENTITY_ID]) || empty($rowData[ImportInterface::ENTITY_ID])) {
            $this->addRowError('Invalid row %1', $rowNumber);
        }
    }

    /**
     * Validate Row Data For Only Update Behaviour
     *
     * @param array $rowData
     * @param $rowNumber
     */
    protected function _validateRowForOnlyUpdate(array $rowData, $rowNumber)
    {
        if (!isset($rowData[ImportInterface::ENTITY_ID]) || empty($rowData[ImportInterface::ENTITY_ID])) {
            $this->addRowError('Invalid row %1', $rowNumber);
        }
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
     * Prepare Data For Update
     *
     * @param array $rowData
     * @return array
     * @throws FileSystemException
     * @throws LocalizedException
     */
    private function _prepareDataForUpdate(array $rowData)
    {
        if (!$this->_prepareDataForOnlyUpdate($rowData)) {
            unset($rowData[ImportInterface::ENTITY_ID]);
        }

        return $rowData;
    }

    /**
     * Prepare Data For Only Update
     *
     * @param array $rowData
     * @return array
     */
    private function _prepareDataForOnlyAdd(array $rowData)
    {
        $collection = $this->jobCollectionFactory->create();
        $collection
            ->addFieldToFilter(ImportInterface::ENTITY_ID, $rowData[ImportInterface::ENTITY_ID])
            ->addFieldToFilter(ImportInterface::ENTITY, $rowData[ImportInterface::ENTITY]);
        if ($collection->count()) {
            return null;
        } else {
            unset($rowData[ImportInterface::ENTITY_ID]);
            return $rowData;
        }
    }

    /**
     * Prepare Data For Only Update
     *
     * @param array $rowData
     * @return array
     * @throws FileSystemException
     * @throws LocalizedException
     */
    private function _prepareDataForOnlyUpdate(array $rowData)
    {
        try {
            $this->jobRepository->getById($rowData[ImportInterface::ENTITY_ID]);
            unset($rowData[ImportInterface::ENTITY_ID]);
            return $rowData;
        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * Prepare Map Data if exists
     *
     * @param array $rowData
     * @param $object
     */
    private function setMapDataIfDoesntExist(array $rowData, $object)
    {
        if ($rowData[ImportInterface::MAP]) {
            $mapData = $this->jsonHelper->jsonDecode($rowData[ImportInterface::MAP]);
            foreach ($mapData as $map) {
                /** @var Mapping $newMap */
                $newMap = $this->mappingFactory->create();
                $collection = $newMap->getCollection()
                    ->addFieldToFilter(ImportMappingInterface::ENTITY_ID, $map[ImportMappingInterface::ENTITY_ID])
                    ->addFieldToFilter(ImportMappingInterface::JOB_ID, $map[ImportMappingInterface::JOB_ID]);
                if ($collection->count()) {
                    continue;
                }
                unset($map[ImportInterface::ENTITY_ID]);
                $newMap->setData($map);
                $newMap->setJobId('');
                $object->addMap($newMap);
            }
        }
    }

    /**
     * Prepare Data For Replace
     *
     * @param array $rowData
     * @return array
     * @throws FileSystemException
     * @throws LocalizedException
     */
    private function _prepareDataForReplace(array $rowData)
    {
        $this->_deleteJob($rowData);

        return $this->_prepareDataForUpdate($rowData);
    }

    /**
     * Save Validated Bunches
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
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
                $currentDataSize = strlen($this->phpSerialize($bunchRows));
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
                $rowData = $this->customBunchesData($rowData);
                $this->_processedRowsCount++;
                if ($this->validateRow($rowData, $source->key())) {
                    $rowSize = strlen($this->jsonHelper->jsonEncode($rowData));
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
     * Retrieve All Fields Source
     *
     * @return array
     */
    public function getAllFields()
    {
        return [];
    }
}
