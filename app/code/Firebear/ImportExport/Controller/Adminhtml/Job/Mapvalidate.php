<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Context;
use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\FilesystemFactory;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class Mapvalidate
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Job
 */
class Mapvalidate extends JobController
{
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var FilesystemFactory
     */
    protected $fileSystem;

    /**
     * Mapvalidate constructor.
     *
     * @param Context $context
     * @param SerializerInterface $serializer
     * @param FilesystemFactory $filesystemFactory
     */
    public function __construct(
        Context $context,
        SerializerInterface $serializer,
        FilesystemFactory $filesystemFactory
    ) {
        parent::__construct($context);

        $this->serializer = $serializer;
        $this->fileSystem = $filesystemFactory;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create($this->resultFactory::TYPE_JSON);
        $messages = [];
        if ($this->getRequest()->isAjax()) {
            //read required fields from xml file
            $type = $this->getRequest()->getParam('type');
            $locale = $this->getRequest()->getParam('language');
            $formData = $this->getRequest()->getParam('form_data');
            $sourceType = $this->getRequest()->getParam('source_type');
            $importData = [];
            foreach ($formData as $data) {
                $index = strstr($data, '+', true);
                $index = str_replace($sourceType . '[', '', $index);
                $index = str_replace(']', '', $index);
                $importData[$index] = substr($data, strpos($data, '+') + 1);
            }
            $importData['records'] = $this->serializer->unserialize($importData['records']);
            $importData['platforms'] = $type;
            $importData['locale'] = $locale;
            $importData['import_source'] = $importData['import_source'] ?? '';
            $importData['file_path'] = $importData['file_path'] ?? '';
            if ($this->getRequest()->getParam('job_id')) {
                $importData['job_id'] = (int)$this->getRequest()->getParam('job_id');
            }
            if (isset($importData['type_file'])) {
                $this->helper->setTypeSource($importData['type_file']);
            }
            $directory = $this->fileSystem->create()->getDirectoryWrite(DirectoryList::ROOT);
            if (!in_array($importData['import_source'], $this->sources)) {
                $importData[$sourceType . '_file_path'] = $importData['file_path'];
            }
            try {
                $result = $this->helper->correctData($importData);
                if ($result) {
                    $messages = $this->helper->processValidate($importData);
                }
            } catch (\Exception $e) {
                return $resultJson->setData(['error' => [$e->getMessage()]]);
            }

            $this->helper->revertLocale();

            $data = count($messages) ? ['error' => $messages] : [];
            return $resultJson->setData($data);
        }
    }
}
