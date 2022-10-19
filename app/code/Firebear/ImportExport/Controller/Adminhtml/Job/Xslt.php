<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Context;
use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;
use Firebear\ImportExport\Model\ImportFactory;
use Firebear\ImportExport\Model\Output\Xslt as OutputXslt;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\FilesystemFactory;
use Magento\Framework\Filesystem\Io\File;

/**
 * Class Xslt
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Job
 */
class Xslt extends JobController
{
    /**
     * @var FilesystemFactory
     */
    protected $fileSystem;

    /**
     * @var ImportFactory
     */
    protected $importFactory;

    /**
     * @var File
     */
    protected $file;

    /**
     * @var OutputXslt
     */
    protected $modelOutput;

    /**
     * Xslt constructor.
     *
     * @param Context $context
     * @param FilesystemFactory $filesystemFactory
     * @param File $file
     * @param ImportFactory $importFactory
     * @param OutputXslt $modelOutput
     */
    public function __construct(
        Context $context,
        FilesystemFactory $filesystemFactory,
        File $file,
        ImportFactory $importFactory,
        OutputXslt $modelOutput
    ) {
        parent::__construct($context);

        $this->fileSystem = $filesystemFactory;
        $this->importFactory = $importFactory;
        $this->file = $file;
        $this->modelOutput = $modelOutput;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create($this->resultFactory::TYPE_JSON);
        $messages = [];
        if ($this->getRequest()->isAjax()) {
            //read required fields from xml file
            $formData = $this->getRequest()->getParam('form_data');
            $importData = [];

            foreach ($formData as $data) {
                $index = strstr($data, '+', true);
                $importData[$index] = substr($data, strpos($data, '+') + 1);
            }
            $directory = $this->fileSystem->create()->getDirectoryWrite(DirectoryList::ROOT);
            if ($importData['import_source'] != 'file') {
                if (!in_array($importData['import_source'], ['rest', 'soap']) && !empty($importData['file_path'])) {
                    $importData[$importData['import_source'] . '_file_path'] = $importData['file_path'];
                }
                $importModel = $this->importFactory->create();
                $importModel->setData($importData);
                $source = $importModel->getSource();
                $source->setFormatFile($importData['type_file']);
                $file = $source->uploadSource();
            } else {
                $file = $directory->getAbsolutePath() . "/" . $importData['file_path'];
            }
            if (strpos($file, $directory->getAbsolutePath()) === false) {
                $file = $directory->getAbsolutePath() . "/" . $file;
            }
            $dest = $this->file->read($file);
            $messages = [];
            try {
                $result = $this->modelOutput->convert($dest, $importData['xslt']);
                return $resultJson->setData(
                    [
                        'result' => $result
                    ]
                );
            } catch (\Exception $e) {
                $messages[] = $e->getMessage();
            }

            return $resultJson->setData(
                [
                    'error' => $messages
                ]
            );
        }
    }
}
