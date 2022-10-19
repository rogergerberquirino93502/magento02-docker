<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Context;
use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\UrlInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Firebear\ImportExport\Model\Import\File\Validator\NotProtectedExtension;
use ZipArchive as Archive;

/**
 * Class Fileload
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Job
 */
class Fileload extends JobController
{
    const PATH = 'importexport';

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * @var UploaderFactory
     */
    private $uploaderFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var NotProtectedExtension
     */
    private $validator;

    /**
     * @var Archive
     */
    private $archive;

    /**
     * Fileload constructor
     *
     * @param Context $context
     * @param Archive $archive
     * @param Filesystem $filesystem
     * @param UploaderFactory $uploaderFactory
     * @param StoreManagerInterface $storeManager
     * @param NotProtectedExtension $validator
     */
    public function __construct(
        Context $context,
        Archive $archive,
        Filesystem $filesystem,
        UploaderFactory $uploaderFactory,
        StoreManagerInterface $storeManager,
        NotProtectedExtension $validator
    ) {
        $this->archive = $archive;
        $this->fileSystem = $filesystem;
        $this->uploaderFactory = $uploaderFactory;
        $this->storeManager = $storeManager;
        $this->validator = $validator;

        parent::__construct($context);
    }

    /**
     * Upload file via ajax
     */
    public function execute()
    {
        $data = [];
        /** @var \Magento\Framework\Controller\Result\Json $json */
        $json = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $uploader = $this->uploaderFactory->create([
                'fileId' => 'file_upload',
                'validator' => $this->validator
            ]);

            $media = $this->fileSystem->getDirectoryRead(DirectoryList::MEDIA);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(true);

            $extension = $uploader->getFileExtension();
            if (in_array($extension, ['tar', 'gz'])) {
                $uploader->skipDbProcessing(true);
            }

            $data = $uploader->save($media->getAbsolutePath(self::PATH));

            if (in_array($extension, ['tar', 'gz'])) {
                $phar = new \PharData($data['path'] . $data['file']);
                $phar->extractTo($data['path'], null, true);
                $fileName = $phar->getFilename();
                $data['type'] = $phar->getExtension();
                $data['file'] = $fileName;
                $data['path'] = $data['path'];
                $data['size'] = $phar->getSize();
            } elseif ($extension == 'zip') {
                $file = $data['path'] . $data['file'];
                $open = $this->archive->open($file);
                if (true === $open) {
                    $archiveFileName = $this->archive->getNameIndex(0);
                    $extension = $this->getExtension($archiveFileName);
                    $newFileName = preg_replace('/\.zip$/i', '.' . $extension, $file);
                    $newArchiveFileName = basename($newFileName);

                    if ($this->archive->renameIndex(0, $newArchiveFileName)) {
                        $this->archive->extractTo(dirname($newFileName), $newArchiveFileName);
                    }
                    $this->archive->close();
                }

                $data['type'] = $extension;
                $data['file'] = dirname($data['file']) . '/' . $newArchiveFileName;
            }

            $data['url'] = $this->getTmpMediaUrl($data['file']);
            $data['path'] = $this->fileSystem->getDirectoryRead(DirectoryList::ROOT)
                ->getRelativePath($data['path']);

            unset($data['tmp_name']);
        } catch (\Exception $e) {
            $data = [
                'error' => $e->getMessage(),
                'errorcode' => $e->getCode()
            ];
        }
        return $json->setData($data);
    }

    /**
     * Get file extension
     *
     * @param string $filename
     * @return string
     */
    private function getExtension($filename)
    {
        return strtolower(
            substr($filename, strrpos($filename, '.') + 1)
        );
    }

    /**
     * @return string
     */
    private function getBaseTmpMediaUrl()
    {
        $store = $this->storeManager->getStore();
        return $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
    }

    /**
     * @param $file
     * @return string
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
