<?php
namespace Firebear\ImportExport\Model\Import;

use Firebear\ImportExport\Api\Data\ExportEventInterface;
use Firebear\ImportExport\Api\Data\ExportInterface;
use Firebear\ImportExport\Model\ExportJob\Event;
use Firebear\ImportExport\Model\ExportJob\EventFactory;
use Firebear\ImportExport\Traits\Import\Entity as ImportTrait;
use InvalidArgumentException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Zend_Validate_Exception;
use Exception;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\AbstractEntity;
use Magento\ImportExport\Model\Import\AbstractSource;
use Magento\ImportExport\Model\ImportFactory;
use Magento\Framework\Exception\FileSystemException;
use Firebear\ImportExport\Model\Source\Import\Behavior\Jobs as JobsBehavior;
use Firebear\ImportExport\Api\ExportJobRepositoryInterface;
use Firebear\ImportExport\Model\ExportJob\Converter as ExportJobConverter;
use Firebear\ImportExport\Model\ResourceModel\ExportJob\CollectionFactory as ExportJobCollectionFactory;

/**
 * Class ExportJobs
 * @package Firebear\ImportExport\Model\Import
 */
class ExportJobs extends AbstractEntity implements ImportAdapterInterface
{
    use ImportTrait;

    /**
     * Entity Type Code
     */
    const ENTITY_TYPE_CODE = 'export_jobs';

    /**
     * @var ResourceConnection
     */
    protected $_resource;

    /**
     * @var array
     */
    private $_alreadyDeleted = [];

    /**
     * @var ExportJobRepositoryInterface
     */
    protected $exportJobRepository;

    /**
     * @var ExportJobConverter
     */
    protected $exportJobConverter;

    /**
     * @var ExportJobCollectionFactory
     */
    protected $exportJobCollectionFactory;

    /**
     * @var EventFactory
     */
    protected $eventFactory;

    /**
     * @var array
     */
    protected $serializedColumns = [
        ExportInterface::BEHAVIOR_DATA,
        ExportInterface::EXPORT_SOURCE,
        ExportInterface::SOURCE_DATA
    ];

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param ImportFactory $importFactory
     * @param EventFactory $eventFactory
     * @param ExportJobRepositoryInterface $exportJobRepository
     * @param ExportJobConverter $exportJobConverter
     * @param ExportJobCollectionFactory $exportJobCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        ImportFactory $importFactory,
        EventFactory $eventFactory,
        ExportJobRepositoryInterface $exportJobRepository,
        ExportJobConverter $exportJobConverter,
        ExportJobCollectionFactory $exportJobCollectionFactory,
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

        $this->eventFactory = $eventFactory;
        $this->output = $context->getOutput();
        $this->_resource = $context->getResource();
        $this->_importExportData = $context->getImportExportData();
        $this->_resourceHelper = $context->getResourceHelper();
        $this->jsonHelper = $context->getJsonHelper();
        $this->_dataSourceModel = $context->getDataSourceModel();
        $this->exportJobConverter = $exportJobConverter;
        $this->exportJobCollectionFactory = $exportJobCollectionFactory;
        $this->exportJobRepository = $exportJobRepository;
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

                $rowData = $this->verifyEnclosure($rowData);

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
     * Update enclosure if it is empty
     * @param array $rowData
     * @return array
     */
    protected function verifyEnclosure($rowData)
    {
        if (isset($rowData['behavior_data'])) {
            $behavior = $rowData['behavior_data'];
            $enclosure = '"behavior_field_enclosure":""';
            $enclosureReplace = '"behavior_field_enclosure":"\""';
            if (strpos($behavior, $enclosure) !== false) {
                $behavior = str_replace($enclosure, $enclosureReplace, $behavior);
                $rowData['behavior_data'] = $behavior;
            }
        }
        return $rowData;
    }

    /**
     * Save Attribute
     *
     * @param array|null $rowData
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _saveJob($rowData)
    {
        if ($rowData) {
            $object = $this->exportJobConverter->getDataObjectByData($rowData);
            if (array_key_exists(ExportEventInterface::EVENT, $rowData)) {
                $this->setEventDataIfExist($rowData, $object);
            }
            $this->unserializeFields($object);
            $this->exportJobRepository->save(
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
     * Prepare Event Data if exists in import file
     *
     * @param array $rowData
     * @param $object
     */
    private function setEventDataIfExist(array $rowData, $object)
    {
        if ($rowData[ExportInterface::EVENT]) {
            $eventData = $this->jsonHelper->jsonDecode($rowData[ExportInterface::EVENT]);
            /** @var Event $event */
            $event = $this->eventFactory->create();
            foreach ($eventData as $data) {
                $collection = $event->getCollection()
                    ->addFieldToFilter(ExportEventInterface::EVENT, $data[ExportEventInterface::EVENT])
                    ->addFieldToFilter(ExportEventInterface::JOB_ID, $data[ExportEventInterface::JOB_ID]);
                if ($collection->count()) {
                    continue;
                }
                unset($data[ExportEventInterface::JOB_ID]);
                $event->setData($data);
                $object->addEvent($event);
            }
        }
    }

    /**
     * Delete Export Job
     *
     * @param array $rowData
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _deleteJob(array $rowData)
    {
        $entityId = $rowData[ExportInterface::ENTITY_ID];
        if (!in_array($entityId, $this->_alreadyDeleted)) {
            $time = explode(" ", microtime());
            $startTime = $time[0] + $time[1];

            try {
                $this->exportJobRepository->deleteById($entityId);
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
            /* check that row is already validated */
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
        if (!isset($rowData[ExportInterface::ENTITY_ID]) || empty($rowData[ExportInterface::ENTITY_ID])) {
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
        if (!isset($rowData[ExportInterface::ENTITY_ID]) || empty($rowData[ExportInterface::ENTITY_ID])) {
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
        if (!isset($rowData[ExportInterface::ENTITY_ID]) || empty($rowData[ExportInterface::ENTITY_ID])) {
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
        if (!isset($rowData[ExportInterface::ENTITY_ID]) || empty($rowData[ExportInterface::ENTITY_ID])) {
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
        if (!isset($rowData[ExportInterface::ENTITY_ID]) || empty($rowData[ExportInterface::ENTITY_ID])) {
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
    protected function _prepareDataForUpdate(array $rowData)
    {
        if (!$this->_prepareDataForOnlyUpdate($rowData)) {
            unset($rowData[ExportInterface::ENTITY_ID]);
        }

        return $rowData;
    }

    /**
     * Prepare Data For Only Update
     *
     * @param array $rowData
     * @return array|null
     */
    private function _prepareDataForOnlyAdd(array $rowData)
    {
        try {
            $this->exportJobRepository->getById($rowData[ExportInterface::ENTITY_ID]);
            return null;
        } catch (\Exception $exception) {
            unset($rowData[ExportInterface::ENTITY_ID]);
            $this->addLogWriteln(
                __($exception->getMessage(), $rowData[ExportInterface::ENTITY_ID]),
                $this->output,
                'info'
            );
        }

        return $rowData;
    }

    /**
     * Prepare Data For Only Update
     *
     * @param array $rowData
     * @return array|null
     */
    private function _prepareDataForOnlyUpdate(array $rowData)
    {
        try {
            $this->exportJobRepository->getById($rowData[ExportInterface::ENTITY_ID]);
        } catch (\Exception $exception) {
            $rowData = null;
        }

        return $rowData;
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
     * @throws LocalizedException
     * @throws Zend_Validate_Exception
     * @throws Exception
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
