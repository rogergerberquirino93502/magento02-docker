<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model;

use Firebear\ImportExport\Model\Import\Adapter;
use Firebear\ImportExport\Model\Source\Type\File\Config;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class Import
 *
 * @package Firebear\ImportExport\Model
 */
class Import extends \Magento\ImportExport\Model\Import
{
    use \Firebear\ImportExport\Traits\General;

    const FIREBEAR_ONLY_UPDATE = 'update';
    const FIREBEAR_ONLY_ADD = 'add';

    /**
     * Limit displayed errors on Import History page.
     */
    const LIMIT_VISIBLE_ERRORS = 5;

    const CREATE_ATTRIBUTES_CONF_PATH = 'firebear_importexport/general/create_attributes';

    /**
     * @var \Firebear\ImportExport\Model\Source\ConfigInterface
     */
    protected $config;

    /**
     * @var \Firebear\ImportExport\Helper\Data
     */
    protected $helper;

    /**
     * @var \Firebear\ImportExport\Helper\Additional
     */
    protected $additional;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    protected $timezone;

    /**
     * @var \Firebear\ImportExport\Model\Source\Type\AbstractType
     */
    protected $source;

    /**
     * @var ConsoleOutput
     */
    protected $output;

    /**
     * @var array
     */
    protected $errorMessages;

    /**
     * @var \Firebear\ImportExport\Model\Source\Factory
     */
    protected $factory;

    /**
     * @var \Magento\Framework\FilesystemFactory
     */
    protected $filesystemFactory;

    /**
     * @var Config
     */
    protected $typeConfig;

    /**
     * @var array|mixed|null
     */
    protected $platforms;

    protected $importConfig;

    public $outputModel;

    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $file;

    /**
     * @var \Magento\Framework\Filesystem\File\WriteFactory
     */
    protected $fileWrite;

    /**
     * Import constructor.
     *
     * @param Source\ConfigInterface $config
     * @param \Firebear\ImportExport\Helper\Data $helper
     * @param \Firebear\ImportExport\Helper\Additional $additional
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\ImportExport\Helper\Data $importExportData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $coreConfig
     * @param Source\Import\Config $importConfig
     * @param \Magento\ImportExport\Model\Import\Entity\Factory $entityFactory
     * @param \Magento\ImportExport\Model\Export\Adapter\CsvFactory $csvFactory
     * @param \Magento\Framework\HTTP\Adapter\FileTransferFactory $httpFactory
     * @param \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory
     * @param \Magento\ImportExport\Model\Source\Import\Behavior\Factory $behaviorFactory
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
     * @param \Magento\ImportExport\Model\History $importHistoryModel
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $localeDate
     * @param Source\Factory $factory
     * @param Source\Platform\Config $configPlatforms
     * @param ConsoleOutput $output
     * @param Source\Import\Config $importConfig
     * @param array $data
     */
    public function __construct(
        \Firebear\ImportExport\Model\Source\ConfigInterface $config,
        \Firebear\ImportExport\Helper\Data $helper,
        \Firebear\ImportExport\Helper\Additional $additional,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\ImportExport\Helper\Data $importExportData,
        \Magento\Framework\App\Config\ScopeConfigInterface $coreConfig,
        \Firebear\ImportExport\Model\Source\Import\Config $importConfig,
        \Magento\ImportExport\Model\Import\Entity\Factory $entityFactory,
        \Magento\ImportExport\Model\Export\Adapter\CsvFactory $csvFactory,
        \Magento\Framework\HTTP\Adapter\FileTransferFactory $httpFactory,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \Magento\ImportExport\Model\Source\Import\Behavior\Factory $behaviorFactory,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magento\ImportExport\Model\History $importHistoryModel,
        \Magento\Framework\Stdlib\DateTime\DateTime $localeDate,
        \Firebear\ImportExport\Model\Source\Factory $factory,
        \Firebear\ImportExport\Model\Source\Platform\Config $configPlatforms,
        \Magento\Framework\FilesystemFactory $filesystemFactory,
        \Firebear\ImportExport\Model\ResourceModel\Import\Data $importData,
        \Magento\Framework\Filesystem\Io\File $file,
        \Magento\Framework\Filesystem\File\WriteFactory $fileWrite,
        \Firebear\ImportExport\Model\Output\Xslt $modelOutput,
        Config $typeConfig,
        ConsoleOutput $output,
        array $data = []
    ) {
        $this->config = $config;
        $this->helper = $helper;
        $this->additional = $additional;
        $this->timezone = $timezone;
        $this->output = $output;
        $this->factory = $factory;
        $this->platforms = $configPlatforms->get();
        $this->importConfig = $importConfig;
        $this->filesystemFactory = $filesystemFactory;
        $this->typeConfig = $typeConfig;
        $this->outputModel = $modelOutput;
        $this->file = $file;
        $this->fileWrite = $fileWrite;

        parent::__construct(
            $logger,
            $filesystem,
            $importExportData,
            $coreConfig,
            $importConfig,
            $entityFactory,
            $importData,
            $csvFactory,
            $httpFactory,
            $uploaderFactory,
            $behaviorFactory,
            $indexerRegistry,
            $importHistoryModel,
            $localeDate,
            $data
        );
    }

    /**
     * Remove large objects
     */
    public function __destruct()
    {
        if (is_object($this->_entityAdapter) && method_exists($this->_entityAdapter, '__destruct')) {
            $this->_entityAdapter->__destruct();
        }
    }

    /**
     * Check if remote file was modified since the last import
     *
     * @param $timestamp
     *
     * @return bool
     */
    public function checkModified($timestamp)
    {
        if ($this->getSource()) {
            return $this->getSource()->checkModified($timestamp);
        }

        return true;
    }

    /**
     * Download remote source file to temporary directory
     *
     * @TODO change the code to show exceptions on frontend instead of 503 error.
     * @return null|string
     * @throws LocalizedException
     */
    public function uploadSource()
    {
        $result = null;

        if ($this->getImportSource() && $this->getImportSource() != 'file') {
            $source = $this->getSource();
            try {
                $result = $source->uploadSource();
            } catch (\Exception $e) {
                throw new LocalizedException(__($e->getMessage()));
            }
        }
        if ($result) {
            return $result;
        }

        return parent::uploadSource();
    }

    /**
     * Return Platform
     *
     * @param string $name
     * @param string $entity
     * @return null|\Firebear\ImportExport\Model\Source\Platform\AbstractPlatform
     */
    public function getPlatform($name, $entity)
    {
        if ($name && $entity && isset($this->platforms[$entity][$name]['model'])) {
            return $this->factory->create($this->platforms[$entity][$name]['model']);
        }
        return null;
    }

    /**
     * Return Platform PreSet Map
     *
     * @param string $name
     * @param string $entity
     * @return mixed[]
     */
    private function prepareMap($name, $entity)
    {
        $map = $this->getData('map') ?: [];
        $keys = array_column($map, 'system');
        $fields = $this->platforms[$entity][$name]['fields'] ?? [];
        if (is_array($fields)) {
            foreach ($fields as $field => $data) {
                /* skip predefined attributes if a custom rule is set */
                if (in_array($data['reference'], $keys)) {
                    continue;
                }
                $map[] = [
                    'system' => $data['reference'],
                    'import' => $field,
                    'default' => $data['default'] ?: null,
                ];
            }
        }
        $this->setData('map', $map);
    }

    /**
     * Validates source file and returns validation result.
     *
     * @param \Magento\ImportExport\Model\Import\AbstractSource $source
     *
     * @return bool
     * @throws LocalizedException
     */
    public function validateSource(\Magento\ImportExport\Model\Import\AbstractSource $source)
    {
        $this->addLogWriteln(__('Begin data validation'), $this->output, 'comment');
        /** @var \Firebear\ImportExport\Traits\Import\Map $source */
        $source->setReplaceWithDefault(
            $this->getData('replace_default_value')
        );
        $source->setPlatform(
            $this->getPlatform(
                $this->getData('platforms'),
                $this->getData('entity')
            )
        );
        try {
            $this->prepareMap($this->getData('platforms'), $this->getData('entity'));
            if (!$source->getMap()) {
                $source->setMap($this->getData('map'));
            }
            $source->setReplacing($this->getData('replacing'));

            $adapter = $this->_getEntityAdapter()->setSource($source);
            $adapter->setLogger($this->_logger);
            $adapter->setOutput($this->output);

            $this->_importData->setJobId($this->getData('job_id'));
            $this->_importData->setOffset($this->getData('offset'));
            $this->_importData->setFile($this->getData('file'));

            $errorAggregator = $adapter->validateData();
        } catch (\Exception $e) {
            $errorAggregator = $this->getErrorAggregator();
            $this->addLogWriteln($e->getMessage(), $this->output, 'error');
            $errorAggregator->addError(
                \Magento\ImportExport\Model\Import\Entity\AbstractEntity::ERROR_CODE_SYSTEM_EXCEPTION . '. '
                . $e->getMessage(),
                ProcessingError::ERROR_LEVEL_CRITICAL,
                null,
                null,
                null,
                $e->getMessage()
            );
        }

        $this->addLogComment(
            __(
                'Checked rows: %1, checked entities: %2, invalid rows: %3, total errors: %4',
                $this->getProcessedRowsCount(),
                $this->getProcessedEntitiesCount(),
                $this->getErrorAggregator()->getInvalidRowsCount(),
                $this->getErrorAggregator()->getErrorsCount()
            )
        );

        $this->showErrors();
        $result = !$errorAggregator->isErrorLimitExceeded();
        if ($result) {
            if ($this->isExistRowsForImport()) {
                if ($this->isImportAllowed()) {
                    $message = __('Import data validation is complete.');
                } else {
                    $message = __('The file is valid, but we can\'t import it for some reason.');
                }
                $this->addLogWriteln($message, $this->output, 'info');
            } else {
                $this->addLogComment(
                    __('Data validation failed. Please fix the errors and upload the file again.')
                );
            }
        }
        return $result;
    }

    /**
     * check if import is successful
     *
     * @return bool
     */
    public function isSuccessful()
    {
        return !$this->getErrorAggregator()->isErrorLimitExceeded() && $this->isExistRowsForImport();
    }

    /**
     * check if exist rows for import
     *
     * @return bool
     */
    public function isExistRowsForImport()
    {
        return $this->getProcessedRowsCount() > $this->getErrorAggregator()->getInvalidRowsCount();
    }

    /**
     * @return Source\Type\AbstractType
     * @throws LocalizedException
     */
    public function getSource()
    {
        if (!$this->source) {
            $sourceType = $this->getImportSource();
            try {
                $this->source = $this->additional->getSourceModelByType($sourceType);
                $this->source->setData($this->getData());
            } catch (\Exception $e) {
                throw new LocalizedException(__($e->getMessage()));
            }
        }

        return $this->source;
    }

    /**
     * @param \Magento\ImportExport\Model\Import\AbstractSource $source
     * @return $this
     */
    public function setSource(\Magento\ImportExport\Model\Import\AbstractSource $source)
    {
        $this->source = $source;
        return $this;
    }

    /**
     * @param mixed $debugData
     *
     * @return $this
     */
    public function addLogComment($debugData)
    {

        if (is_array($debugData)) {
            $this->_logTrace = array_merge($this->_logTrace, $debugData);
        } else {
            $this->_logTrace[] = $debugData;
        }

        if (!is_array($debugData)) {
            $this->_logger->debug($debugData);
            if ($this->output) {
                $this->output->writeln($debugData);
            }
        } else {
            foreach ($debugData as $message) {
                if ($message instanceof \Magento\Framework\Phrase) {
                    if ($this->output) {
                        $this->output->writeln($message->__toString());
                    }
                    $this->_logger->debug($message->__toString());
                } else {
                    if ($this->output) {
                        $this->output->writeln($message);
                    }
                    $this->_logger->debug($message);
                }
            }
        }

        return $this;
    }

    /**
     * @return \Magento\ImportExport\Model\Import\AbstractEntity|\Magento\ImportExport\Model\Import\Entity\AbstractEntity
     * @throws LocalizedException
     */
    protected function _getEntityAdapter()
    {
        $adapter = parent::_getEntityAdapter();
        $adapter->setLogger($this->_logger);
        $adapter->setOutput($this->output);

        return $adapter;
    }

    public function getEntityAdapter()
    {
        return $this->_getEntityAdapter();
    }

    /**
     * @return mixed
     */
    public function getTypeClass($typeData)
    {
        $data = $this->typeConfig->get();
        $types = $data['import'][$typeData];
        $model = $types['model'];
        if (isset($types[$this->getTypeSource()])) {
            $model = $types[$this->getTypeSource()]['model'];
        }
        return $model;
    }

    /**
     * Load categories map
     *
     * @return mixed
     * @throws LocalizedException
     */
    public function getCategories($sourceData)
    {
        $adapter = $this->_getEntityAdapter();
        $errorMessage = __('Unknown Error');
        $platform = $this->getPlatform(
            $sourceData['platforms'] ?? null,
            $sourceData['entity'] ?? null
        );

        if ($sourceData['import_source'] == 'file') {
            $another = 0;
            $source = Adapter::findAdapterFor(
                $this->getTypeClass($sourceData['type_file']),
                $sourceData['file_path'],
                $this->filesystemFactory->create()->getDirectoryWrite(DirectoryList::ROOT),
                $platform,
                $sourceData
            );
        } else {
            $isGateway = $platform && $platform->isGateway();
            if ($isGateway) {
                $data['get_only_first_page'] = true;
                $source = $platform->getSource($sourceData);
                $this->setSource($source);
                $this->setData($sourceData);
                $this->getSource()->setData($sourceData);
            } else {
                $directory = $this->filesystemFactory->create()->getDirectoryWrite(DirectoryList::ROOT);
                $this->setImportSource($sourceData['import_source']);
                $this->setData($sourceData);
                $this->getSource()->setData($sourceData);
                $result = null;
                $source = $this->getSource();
                $source->setFormatFile($sourceData['type_file']);
                try {
                    $result = $source->uploadSource();
                } catch (\Exception $e) {
                    $errorMessage = __($e->getMessage());
                    if (strpos($errorMessage, 'ftp_get()') !== false) {
                        $errorMessage = __('Unable to open your file. Please make sure File Path is correct.');
                    }
                }
                if ($result) {
                    $another = 1;
                    $source = Adapter::findAdapterFor(
                        $this->getTypeClass($sourceData['type_file']),
                        $this->uploadSource(),
                        $directory,
                        $platform,
                        $sourceData
                    );
                } else {
                    return $errorMessage;
                }
            }
        }

        if (isset($sourceData['type_file']) && $sourceData['type_file'] == 'xml' && $sourceData['xml_switch']) {
            $directory = $this->filesystemFactory->create()->getDirectoryWrite(DirectoryList::ROOT);
            if ($another) {
                $file = $result;
            } else {
                $file = $directory->getAbsolutePath() . "/" . $sourceData['file_path'];
            }
            if (strpos($file, $directory->getAbsolutePath()) === false) {
                $file = $directory->getAbsolutePath() . "/" . $file;
            }
            $dest = $this->file->read($file);

            try {
                $result = $this->outputModel->convert($dest, $sourceData['xslt']);
            } catch (\Exception $e) {
                throw new LocalizedException(__($e->getMessage()));
            }

            $pathInfo = pathinfo($file);
            $destFile = $pathInfo['dirname'] . "/" . $pathInfo['filename'] . "_xslt." . $pathInfo['extension'];
            $file = $this->fileWrite->create(
                $destFile,
                \Magento\Framework\Filesystem\DriverPool::FILE,
                "w+"
            );
            $file->write($result);
            $file->close();
            $source = Adapter::findAdapterFor(
                $this->getTypeClass($sourceData['type_file']),
                $destFile,
                $this->filesystemFactory->create()->getDirectoryWrite(DirectoryList::ROOT),
                $platform,
                $sourceData
            );
        }

        $adapter->setSource($source);
        $fieldName = 'categories';

        if (isset($sourceData['mappingData'])) {
            foreach ($sourceData['mappingData'] as $sourceDataMapItem) {
                if (isset($sourceDataMapItem['source_data_system']) &&
                    $sourceDataMapItem['source_data_system'] == 'categories'
                ) {
                    $fieldName = $sourceDataMapItem['source_data_import'];
                }
            }
        }

        return $adapter->getCategoriesMap($fieldName);
    }

    /**
     * Get entity adapter class string
     *
     * @return string
     */
    public function getEntityClassName()
    {
        return get_class($this->_getEntityAdapter());
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function getEntityBehaviors()
    {
        $behaviourData = [];
        $entities = $this->importConfig->getEntities();
        foreach ($entities as $entityCode => $entityData) {
            $behaviorClassName = isset($entityData['behaviorModel']) ? $entityData['behaviorModel'] : null;
            if ($behaviorClassName && class_exists($behaviorClassName)) {
                /** @var $behavior \Magento\ImportExport\Model\Source\Import\AbstractBehavior */
                $behavior = $this->_behaviorFactory->create($behaviorClassName);

                $behaviourData[$entityCode] = [
                    'token' => $behaviorClassName,
                    'code' => $behavior->getCode() . '_behavior',
                    'notes' => $behavior->getNotes($entityCode),
                ];
            } else {
                throw new LocalizedException(
                    __('The behavior token for %1 is invalid.', $entityCode)
                );
            }
        }
        return $behaviourData;
    }

    public function importSourcePart($file, $offset, $job, $show)
    {
        $this->setData('entity', $this->getEntity());
        $this->setData('behavior', $this->getBehavior());

        if (0 == $offset) {
            $this->addLogComment(
                __('Begin import of "%1" with "%2" behavior', $this->getEntity(), $this->getBehavior())
            );
        }

        $status = $this->processImportPart($file, $offset, $job);
        if ($status) {
            $this->showErrors();
            if (empty($this->getProcessedEntitiesCount())) {
                $status = false;
                $this->addLogComment(__('No data imported.'));
            } elseif (empty($this->getErrorAggregator()->getErrorsCount())) {
                $this->addLogComment(__('The import was successful.'));
            }
        }
        return $status;
    }

    protected function processImportPart($file, $offset, $job)
    {
        return $this->_getEntityAdapter()->importDataPart($file, $offset, $job);
    }

    public function setErrorAggregator($errorAggregator)
    {
        $this->_getEntityAdapter()->setErrorAggregator($errorAggregator);
        $this->_getEntityAdapter()->setErrorMessages();
    }

    /**
     * @throws LocalizedException
     */
    public function showErrors()
    {
        foreach ($this->getErrorAggregator()->getRowsGroupedByErrorCode() as $errorMessage => $rows) {
            $error = $errorMessage . ' ' . __('in rows') . ': ' . implode(', ', $rows);
            $this->addLogWriteln($error, $this->output, 'error');
        }
    }

    public function validateCheck(\Magento\ImportExport\Model\Import\AbstractSource $source)
    {
        $this->addLogComment(__('Begin data validation'));

        $errorAggregator = $this->getErrorAggregator();
        $errorAggregator->initValidationStrategy(
            $this->getData(self::FIELD_NAME_VALIDATION_STRATEGY),
            $this->getData(self::FIELD_NAME_ALLOWED_ERROR_COUNT)
        );

        try {
            $adapter = $this->_getEntityAdapter()->setSource($source);
            $adapter->validateData(0);
        } catch (\Exception $e) {
            $errorAggregator->addError(
                \Magento\ImportExport\Model\Import\Entity\AbstractEntity::ERROR_CODE_SYSTEM_EXCEPTION,
                ProcessingError::ERROR_LEVEL_CRITICAL,
                null,
                null,
                null,
                $e->getMessage()
            );
        }

        $messages = $this->getOperationResultMessages($errorAggregator);
        $this->addLogComment($messages);

        $result = !$errorAggregator->getErrorsCount();
        if ($result) {
            $this->addLogComment(__('Import data validation is complete.'));
        }
        return $result;
    }

    /**
     * Return operation result messages
     *
     * @param ProcessingErrorAggregatorInterface $validationResult
     * @return string[]
     * @throws LocalizedException
     */
    public function getOperationResultMessages(ProcessingErrorAggregatorInterface $validationResult)
    {
        $messages = parent::getOperationResultMessages($validationResult);
        if ($this->getProcessedRowsCount()) {
            if (!$validationResult->isErrorLimitExceeded()) {
                foreach ($validationResult->getRowsGroupedByErrorCode() as $errorMessage => $rows) {
                    $error = $errorMessage . ' ' . __('in row(s)') . ': ' . implode(', ', $rows);
                    array_unshift($messages, $error);
                }
            }
        }
        return $messages;
    }

    /**
     * @param $output
     */
    public function setOuput($output)
    {
        $this->output = $output;
    }

    public function getFireDataSourceModel()
    {
        return $this->_importData;
    }

    public function setNullEntityAdapter()
    {
        $this->_entityAdapter = null;
    }
}
