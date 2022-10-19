<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Store\Model\StoreManagerInterface;
use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class GoogleKeyload
 * @package Firebear\ImportExport\Controller\Adminhtml\Job
 */
class GoogleKeyload extends JobController
{
    /**
     *
     * @var \Magento\Framework\Filesystem
     */
    private $fileSystem;

    /**
     *
     * @var \Magento\Framework\Json\EncoderInterface
     */
    private $jsonEncoder;

    /**
     *
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    private $uploaderFactory;

    /**
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * GoogleKeyload constructor.
     * @param Context $context
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->fileSystem = $filesystem;
        $this->jsonEncoder = $jsonEncoder;
        $this->uploaderFactory = $uploaderFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Upload file via ajax
     */
    public function execute()
    {
        try {
            $paramName = $this->getRequest()->getParam('param_name');

            $uploader = $this->uploaderFactory->create([
                'fileId' => $paramName
            ]);

            $mediaDirectory = $this->fileSystem->getDirectoryWrite(DirectoryList::MEDIA);
            $root = $this->fileSystem->getDirectoryRead(DirectoryList::ROOT);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(true);
            $uploader->setAllowedExtensions(['json']);
            $uploadDir = $mediaDirectory->getAbsolutePath('importexport/');

            if ($mediaDirectory->isWritable($uploadDir)) {
                $result = $uploader->save($uploadDir);
                $result['path'] = str_replace($root->getAbsolutePath(), "", $result['path']);
                unset($result['tmp_name']);
                $result['url'] = $this->getTmpMediaUrl($result['file']);
                $this->getResponse()->setBody($this->jsonEncoder->encode($result));
            } else {
                $errorMessage = 'The folder' . $uploadDir . ' does not exist or does not have enough permissions';
                throw new LocalizedException(__($errorMessage));
            }
        } catch (\Exception $e) {
            $result = [
                'error' => $e->getMessage(),
                'errorcode' => $e->getCode()
            ];
            $this->getResponse()->setBody($this->jsonEncoder->encode($result));
        }
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getBaseTmpMediaUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
            . 'importexport';
    }

    /**
     * @param $file
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getTmpMediaUrl($file)
    {
        return $this->getBaseTmpMediaUrl() . '/' . $this->prepareFile($file);
    }

    /**
     * @param $file
     * @return string
     */
    private function prepareFile($file)
    {
        return ltrim(str_replace('\\', '/', $file), '/');
    }
}
