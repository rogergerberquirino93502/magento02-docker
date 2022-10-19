<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Model\Import as ImportModel;
use Firebear\ImportExport\Model\JobFactory as JobModel;
use Firebear\ImportExport\Api\JobRepositoryInterface as Repository;
use Magento\ImportExport\Controller\Adminhtml\ImportResult;
use Firebear\ImportExport\Model\Source\Platform\Magento;
use Magento\Backend\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Csv;
use Magento\Framework\FilesystemFactory;
use Magento\ImportExport\Block\Adminhtml\Import\Frame\Result;
use Magento\ImportExport\Controller\Adminhtml\ImportResult as ImportResultController;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Block\Adminhtml\Import\Frame\Result as ImportResultBlock;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\ImportExport\Model\Import as MagentoImportModel;
use Magento\ImportExport\Model\Import\Adapter as ImportAdapter;

/**
 * Class Validate
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Job
 */
class Validate extends ImportResultController
{
    /**
     * @var Import
     */
    protected $import;

    /**
     * @var ImportResult
     */
    protected $importResult;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var JobModel
     */
    protected $factory;

    /**
     * @var Repository
     */
    protected $repository;

    /**
     * @var Csv
     */
    protected $csv;

    /**
     * @var FilesystemFactory
     */
    protected $fileSystem;

    /**
     * @var Magento
     */
    protected $magentoPlatforms;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\ImportExport\Model\Report\ReportProcessorInterface $reportProcessor
     * @param \Magento\ImportExport\Model\History $historyModel
     * @param \Magento\ImportExport\Helper\Report $reportHelper
     * @param JobModel $factory
     * @param Repository $repository
     * @param Csv $csv
     * @param FilesystemFactory $fileSystem
     * @param Magento $magentoPlatforms
     * @param ImportResultController $importResult
     * @param ImportModel $import
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\ImportExport\Model\Report\ReportProcessorInterface $reportProcessor,
        \Magento\ImportExport\Model\History $historyModel,
        \Magento\ImportExport\Helper\Report $reportHelper,
        JobModel $factory,
        Repository $repository,
        Csv $csv,
        FilesystemFactory $fileSystem,
        Magento $magentoPlatforms,
        ImportResult $importResult,
        ImportModel $import
    ) {
        parent::__construct($context, $reportProcessor, $historyModel, $reportHelper);
        $this->session = $context->getSession();
        $this->factory = $factory;
        $this->repository = $repository;
        $this->csv = $csv;
        $this->fileSystem = $fileSystem;
        $this->import = $import;
        $this->importResult = $importResult;
        $this->magentoPlatforms = $magentoPlatforms;
    }

    /**
     * Validate uploaded files action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /*unset section*/
        $session = $this->session;
        $session->setData('missing_attributes', null);
        $session->setData('missing_attribute_values', null);

        $jobId            = $this->getRequest()->getParam('id');
        $createAttrValues = $this->getRequest()->getParam('create_attr_values');

        $job               = $this->repository->getById($jobId);
        $behaviorData      = $job->getBehaviorData();
        $sourceData        = $job->getSourceData();
        $mapAttributesData = $job->getMap();

        $data = array_merge(
            ['entity' => $job->getEntity()],
            $behaviorData,
            ['import_source' => $job->getImportSource()],
            $sourceData,
            [$job->getImportSource() . '_file_path' => $sourceData['file_path']],
            ['map' => $mapAttributesData]
        );
        /** @var \Magento\Framework\View\Result\Layout $resultLayout */
        $resultLayout = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
        /** @var $resultBlock ImportResultBlock */
        $resultBlock = $resultLayout->getLayout()
            ->createBlock(Result::class)
            ->setTemplate('import/frame/result.phtml');

        if ($data) {
            // common actions
            $resultBlock->addAction(
                'show',
                'import_validation_container'
            );

            /** @var $import MagentoImportModel */
            $import = $this->getImport()->setData($data);
            try {
                if ($data['import_source'] != 'file') {
                    $source = ImportAdapter::findAdapterFor(
                        $import->uploadSource(),
                        $this->fileSystem->create()
                            ->getDirectoryWrite(DirectoryList::ROOT),
                        $data[$import::FIELD_FIELD_SEPARATOR]
                    );
                } else {
                    $source = ImportAdapter::findAdapterFor(
                        $data['file_path'],
                        $this->fileSystem->create()
                            ->getDirectoryWrite(DirectoryList::ROOT),
                        $data[MagentoImportModel::FIELD_FIELD_SEPARATOR]
                    );
                    // create report history
                    $filePath = $this->fileSystem->create()
                        ->getDirectoryWrite(DirectoryList::ROOT)
                        ->getAbsolutePath($data['file_path']);
                    $this->getImport()->createHistoryReport($filePath, $data['entity']);
                }
                $this->processValidationResult(
                    $import->validateSource($source),
                    $resultBlock,
                    $createAttrValues
                );
            } catch (LocalizedException $e) {
                $resultBlock->addError($e->getMessage());
            } catch (\Exception $e) {
                $resultBlock->addError(__('Sorry, but the data is invalid or the file is not uploaded.'));
            }

            return $this->getResponse()->appendBody($resultBlock->toHtml());
        } elseif ($this->getRequest()->isPost() && empty($this->getRequest()->getFiles())) {
            $resultBlock->addError(__('The file was not uploaded.'));

            return $this->getResponse()->appendBody($resultBlock->toHtml());
        }
        $this->messageManager->addError(__('Sorry, but the data is invalid or the file is not uploaded.'));
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('adminhtml/*/index');

        return $resultRedirect;
    }

    /**
     * @param      $validationResult
     * @param      $resultBlock
     * @param null $createAttrValues
     */
    public function processValidationResult($validationResult, $resultBlock, $createAttrValues = null)
    {
        $import = $this->getImport();
        if (!$import->getProcessedRowsCount()) {
            if (!$import->getErrorAggregator()->getErrorsCount()) {
                $resultBlock->addError(__('This file is empty. Please try another one.'));
            } else {
                foreach ($import->getErrorAggregator()->getAllErrors() as $error) {
                    $resultBlock->addError($error->getErrorMessage());
                }
            }
        } else {
            $errorAggregator = $import->getErrorAggregator();
            if (!$validationResult) {
                $resultBlock->addError(
                    __('Data validation failed. Please fix the following errors and upload the file again.')
                );
                $session = $this->session;
                if ($missingAttributes = $session->getData('missing_attributes')) {
                    $resultBlock->addNotice(
                        __(
                            'Attributes <strong id="missing_attributes_list">%1</strong> are not exist in your system.',
                            $missingAttributes
                        )
                    );
                }
                $this->addErrorMessages($resultBlock, $errorAggregator);
                $fileNameForDownload = $this->getImportResult()->createErrorReport($errorAggregator);
                $this->getFileForDownload($fileNameForDownload, $resultBlock, $createAttrValues);

                if ($session->getData('missing_attribute_values')) {
                    $resultBlock->addNotice(
                        __(
                            '<span id="missing_attribute_values_list">'
                            . 'Some attribute values are not exist in your system.</span>'
                        )
                    );
                }
            } else {
                if ($import->isImportAllowed()) {
                    $resultBlock->addSuccess(
                        __('File is valid!')
                    );
                } else {
                    $resultBlock->addError(__('The file is valid, but we can\'t import it for some reason.'));
                }
            }
            $resultBlock->addNotice(
                __(
                    'Checked rows: %1, checked entities: %2, invalid rows: %3, total errors: %4',
                    $import->getProcessedRowsCount(),
                    $import->getProcessedEntitiesCount(),
                    $errorAggregator->getInvalidRowsCount(),
                    $errorAggregator->getErrorsCount()
                )
            );
        }
    }

    /**
     * @param      $fileNameForDownload
     * @param      $resultBlock
     * @param null $createAttrValues
     */
    protected function getFileForDownload($fileNameForDownload, $resultBlock, $createAttrValues = null)
    {
        if ($fileNameForDownload) {
            $filePath     = 'var/import_history/' . $fileNameForDownload;
            $csvProcesser = $this->csv;
            $errorRows    = $csvProcesser->getData($filePath);
            $rows         = $data = [];
            foreach ($errorRows as $rowIndex => $errorRow) {
                if ($rowIndex > 0) {
                    foreach ($errorRow as $key => $value) {
                        $columnName        = $errorRows[0][$key];
                        $data[$columnName] = $value;
                    }
                    $rows[] = $data;
                }
            }
            if (!empty($rows)) {
                $platformObj          = $this->magentoPlatforms;
                $validAttributeValues = $platformObj->createNewAttributeValues($rows);
                if (!$validAttributeValues) {
                    $this->_session->setData('missing_attribute_values', 1);
                }
                if ($createAttrValues) {
                    $platformObj->createNewAttributeValues($rows, true);
                    $resultBlock->addSuccess(
                        __(
                            '<span id="attribute_created">'
                            . 'All missing attribute values were created. Validate your Job again!</span>'
                        )
                    );
                }
            }
        }
    }
    /**
     * @return Import
     * @deprecated
     */
    public function getImport()
    {
        return $this->import;
    }

    /**
     * Get import result
     *
     * @return ImportResult
     */
    public function getImportResult()
    {
        return $this->importResult;
    }
}
