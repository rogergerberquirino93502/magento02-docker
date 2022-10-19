<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Api\Import;

use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Firebear\ImportExport\Api\Import\UploadInterface;

/**
 * File upload command (Service Provider Interface - SPI)
 *
 * @api
 */
class Upload implements UploadInterface
{
    /**
     * Target directory
     */
    const UPLOAD_DIRECTORY = 'importexport';

    /**
     * @var array
     */
    public static $allowedExtensions = ['csv', 'xml', 'ods', 'xlsx', 'zip'];

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var UploaderFactory
     */
    protected $uploaderFactory;

    /**
     * Upload media path
     *
     * @var string|null
     */
    private $uploadPath;

    /**
     * Initialize command
     *
     * @param Filesystem $filesystem
     * @param UploaderFactory $uploaderFactory
     */
    public function __construct(
        Filesystem $filesystem,
        UploaderFactory $uploaderFactory
    ) {
        $this->filesystem = $filesystem;
        $this->uploaderFactory = $uploaderFactory;
    }

    /**
     * Upload file
     *
     * @param string $fileName
     * @param string $uniqueName
     * @return string
     * @throws LocalizedException
     */
    public function execute($fileName = '', $uniqueName = false)
    {
        $uploader = $this->uploaderFactory->create([
            'fileId' => 'file'
        ]);
        $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $uploader->setAllowRenameFiles(true);
        $uploader->setFilesDispersion(true);
        $extension = strtolower($uploader->getFileExtension());

        if (in_array($extension, self::$allowedExtensions)) {
            if ($uniqueName) {
                $fileName = date('Y-m-d-H-m-s');
            }

            $result = $uploader->save(
                $mediaDirectory->getAbsolutePath($this->getAbsolutePath()),
                $this->correctFileName($fileName, $extension)
            );

            $file = $result['path'] . $result['file'];
            if ($extension == 'zip') {
                $zipFile = $file;
                $zip = new \ZipArchive();
                $zip->open($zipFile);
                $file = $zip->getNameIndex(0);
                $zip->extractTo(dirname($zipFile), $file);
                $zip->close();
                unlink($zipFile);
            }

            return 'pub/media/' . $mediaDirectory->getRelativePath($file);
        }
        throw new LocalizedException(__('Unsupported file type'));
    }

    /**
     * Set absolute path
     *
     * @param string $uploadPath
     * @return void
     */
    public function setAbsolutePath($uploadPath)
    {
        $this->uploadPath = $uploadPath;
    }

    /**
     * Retrieve absolute path
     *
     * @return string
     */
    private function getAbsolutePath()
    {
        return $this->uploadPath ?: self::UPLOAD_DIRECTORY;
    }

    /**
     * Add extension to file name if not exist
     *
     * @param $name
     * @param $extension
     *
     * @return string
     */
    private function correctFileName($name, $extension)
    {
        if ($name) {
            $newFileNameInfo = pathinfo($name);
            if (!isset($newFileNameInfo['extension'])
                || $newFileNameInfo['extension'] != $extension
            ) {
                $name .= '.' . $extension;
            }
        }
        return $name;
    }
}
