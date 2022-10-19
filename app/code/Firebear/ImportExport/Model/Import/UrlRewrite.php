<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import;

use Firebear\ImportExport\Model\Import\UrlRewrite\EntityHandler;
use Firebear\ImportExport\Traits\Import\Entity as ImportTrait;
use InvalidArgumentException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\AbstractEntity;
use Magento\ImportExport\Model\Import\AbstractSource;
use Magento\ImportExport\Model\ImportFactory;

/**
 * Class UrlRewrite
 * @package Firebear\ImportExport\Model\Import
 */
class UrlRewrite extends AbstractEntity implements ImportAdapterInterface
{
    use ImportTrait;

    /**
     * Url rewrite_id column name
     */
    const COLUMN_URL_REWRITE_ID = 'url_rewrite_id';

    /**
     * Entity handler
     *
     * @var EntityHandler
     */
    protected $_handler;

    /**
     * Resource connection
     *
     * @var ResourceConnection
     */
    protected $_resource;

    /**
     * Initialize import
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param ImportFactory $importFactory
     * @param EntityHandler $handler
     * @param array $data
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        ImportFactory $importFactory,
        EntityHandler $handler,
        array $data = []
    ) {
        $this->_logger = $context->getLogger();
        $this->output = $context->getOutput();
        $this->_resource = $context->getResource();
        $this->_importExportData = $context->getImportExportData();
        $this->_resourceHelper = $context->getResourceHelper();
        $this->jsonHelper = $context->getJsonHelper();

        parent::__construct(
            $context->getStringUtils(),
            $scopeConfig,
            $importFactory,
            $context->getResourceHelper(),
            $context->getResource(),
            $context->getErrorAggregator(),
            $data
        );

        $this->_handler = $handler;
        $this->_handler->init($this);
    }

    /**
     * Import data rows
     *
     * @return boolean
     */
    protected function _importData()
    {
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            foreach ($bunch as $rowNumber => $rowData) {
                /* validate data */
                if (!$rowData || !$this->validateRow($rowData, $rowNumber)) {
                    continue;
                }

                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNumber);
                    continue;
                }

                /* behavior selector */
                switch ($this->getBehavior()) {
                    case Import::BEHAVIOR_DELETE:
                        $rowData = $this->_handler->prepareRowForDelete($rowData);
                        $this->_delete($rowData);
                        break;
                    case Import::BEHAVIOR_REPLACE:
                        $rowData = $this->_handler->prepareRowForReplace($rowData);
                        $this->_delete($rowData);
                        $this->_save($rowData);
                        break;
                    case Import::BEHAVIOR_ADD_UPDATE:
                        $this->_save(
                            $this->_handler->prepareRowForUpdate($rowData)
                        );
                        break;
                }
            }
        }
        return true;
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
                $this->_handler->validateRowForDelete($rowData, $rowNumber);
                break;
            case Import::BEHAVIOR_REPLACE:
                $this->_handler->validateRowForReplace($rowData, $rowNumber);
                break;
            case Import::BEHAVIOR_ADD_UPDATE:
                $this->_handler->validateRowForUpdate($rowData, $rowNumber);
                break;
        }
        return !$this->getErrorAggregator()->isRowInvalid($rowNumber);
    }

    /**
     * Delete row
     *
     * @param array $rowData
     * @return $this
     */
    protected function _delete(array $rowData)
    {
        $condition = $this->_connection->quoteInto(
            self::COLUMN_URL_REWRITE_ID . ' = ?',
            $rowData[self::COLUMN_URL_REWRITE_ID]
        );
        $this->_connection->delete(
            $this->_resource->getTableName('url_rewrite'),
            $condition
        );
        $this->countItemsDeleted++;
        return $this;
    }

    /**
     * Update entity
     *
     * @param array $rowData
     * @return $this
     */
    protected function _save(array $rowData)
    {
        if (isset($rowData[self::COLUMN_URL_REWRITE_ID])) {
            $this->_connection->insertOnDuplicate(
                $this->_resource->getTableName('url_rewrite'),
                $rowData,
                $this->_getEntityFieldsToUpdate($rowData)
            );
            $this->countItemsUpdated++;
        } else {
            $this->_connection->insert(
                $this->_resource->getTableName('url_rewrite'),
                $rowData
            );
            $this->countItemsCreated++;
        }
        return $this;
    }

    /**
     * Filter the entity that are being updated so we only change fields found in the importer file
     *
     * @param array $rowData
     * @return array
     */
    protected function _getEntityFieldsToUpdate(array $rowData)
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
     * Retrieve All Fields Source
     *
     * @return array
     */
    public function getAllFields()
    {
        return array_keys($this->describeTable());
    }

    /**
     * Retrieve the column descriptions for a table
     *
     * @return array
     */
    protected function describeTable()
    {
        return $this->_connection->describeTable(
            $this->_resource->getTableName('url_rewrite')
        );
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
     * Imported entity type code getter
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        return 'url_rewrite';
    }
}
