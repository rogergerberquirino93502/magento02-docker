<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Type;

use Exception;
use Firebear\ImportExport\Api\Export\History\CompressInterface;
use Firebear\ImportExport\Model\Filesystem\File\ReadFactory;
use Firebear\ImportExport\Model\OneDrive\OneDrive as OneDriveModel;
use Firebear\ImportExport\Model\Source\Factory as SourceFactory;
use GuzzleHttp\Exception\ClientException;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteFactory as DirectoryWriteFactory;
use Magento\Framework\Filesystem\File\WriteFactory as FileWriteFactory;
use Magento\Framework\Stdlib\DateTime\Timezone;
use Microsoft\Graph\Exception\GraphException;
use function sprintf;

/**
 * Class Onedrive
 * @package Firebear\ImportExport\Model\Source\Type
 */
class Onedrive extends AbstractType
{
    /**
     * @var string
     */
    protected $code = 'onedrive';
    /**
     * @var OneDriveModel
     */
    protected $oneDrive;

    /**
     * Onedrive constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param Filesystem $filesystem
     * @param ReadFactory $readFactory
     * @param DirectoryWriteFactory $writeFactory
     * @param FileWriteFactory $fileWrite
     * @param Timezone $timezone
     * @param SourceFactory $factory
     * @param CacheInterface $cache
     * @param CompressInterface $compressCommand
     * @param OneDriveModel $oneDrive
     * @param array $data
     * @throws FileSystemException
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Filesystem $filesystem,
        ReadFactory $readFactory,
        DirectoryWriteFactory $writeFactory,
        FileWriteFactory $fileWrite,
        Timezone $timezone,
        SourceFactory $factory,
        CacheInterface $cache,
        CompressInterface $compressCommand,
        OneDriveModel $oneDrive,
        array $data = []
    ) {
        parent::__construct(
            $scopeConfig,
            $filesystem,
            $readFactory,
            $writeFactory,
            $fileWrite,
            $timezone,
            $factory,
            $cache,
            $compressCommand,
            $data
        );
        $this->oneDrive = $oneDrive;
    }

    /**
     * @return bool
     * @throws Exception
     */
    protected function _getSourceClient()
    {
        return true;
    }

    /**
     * @param int $timestamp
     * @return int|false
     * @throws GraphException
     * @throws LocalizedException
     */
    public function checkModified($timestamp)
    {
        return true;
    }

    /**
     * @param $model
     * @return array
     */
    public function run($model)
    {
        $result = true;
        $errors = [];
        $path = '';

        try {
            $this->setExportModel($model);
            $name = 'export_' . $this->timezone->date()->format('Y_m_d_H_i_s');
            $path = AbstractType::EXPORT_DIR . "/" . $name;
            if ($this->writeFile($path)) {
                $filePath = $this->directory->getAbsolutePath($path);

                $pathOnOneDriveConfig = $this->getData('file_path');
                $pathOnOneDrive = ltrim($pathOnOneDriveConfig, '/');
                $pathOnOneDrive = '/' . $pathOnOneDrive;

                $this->oneDrive->uploadFile($pathOnOneDrive, $filePath);
            }
        } catch (Exception $e) {
            $result = false;
            $errors[] = __('%1', $e->getMessage());
        }

        return [$result, $path, $errors];
    }

    /**
     * @return string
     */
    public function getTempFilePath()
    {
        $fileName = basename($this->getData($this->code . '_file_path'));
        return $this->directory->getAbsolutePath($this->getImportPath() . '/' . $fileName);
    }

    /**
     * @return string
     * @throws FileSystemException
     * @throws GraphException
     * @throws LocalizedException
     */
    public function uploadSource()
    {
        $filePath = $this->getTempFilePath();
        if (!$this->directory->isExist($filePath)) {
            $remoteFilePath = $this->getData($this->code . '_file_path');
            $this->directory->create(dirname($filePath));

            $fileContent = $this->oneDrive->downloadFileContent($remoteFilePath);

            if (!$fileContent) {
                throw new LocalizedException(__("File not found on OneDrive"));
            }

            $this->directory->writeFile($filePath, $fileContent);
        }

        return $filePath;
    }

    /**
     * @param string $importImage
     * @param string $imageSting
     * @return void
     * @throws FileSystemException
     * @throws LocalizedException
     */
    public function importImage($importImage, $imageSting)
    {
        if (preg_match('/\bhttps?:\/\//i', $importImage, $matches)) {
            $this->setUrl($importImage, $imageSting, $matches);
            return;
        }

        $filePath = $this->directory->getAbsolutePath($this->getMediaImportPath() . $imageSting);
        $sourceDir = $this->getData($this->code . '_import_images_file_dir') ?: '/';

        $oneDriveImagePath = $sourceDir;
        if ($sourceDir != '/') {
            $oneDriveImagePath = rtrim($oneDriveImagePath, '/');
            $oneDriveImagePath = ltrim($oneDriveImagePath, '/');
        }

        $oneDriveImagePath = sprintf(
            '/%s/%s',
            $oneDriveImagePath,
            $importImage
        );

        try {
            $imageContent = $this->oneDrive->downloadFileContent($oneDriveImagePath);

            $this->directory->writeFile($filePath, $imageContent);
        } catch (ClientException $e) {
            $message = $e->getMessage();
            $error = (string)$e->getResponse()->getBody();
            if ($error) {
                $errorArray = json_decode($error);
                if (is_object($errorArray) && !empty($errorArray->error->message)) {
                    $message = sprintf(
                        'Image: %s. Error: %s.',
                        $oneDriveImagePath,
                        $errorArray->error->message
                    );
                }
            }
            throw new LocalizedException(__(
                "OneDrive API Exception: " . $message
            ));
        } catch (Exception $e) {
            throw new LocalizedException(__(
                "Error when import image: " . $e->getMessage()
            ));
        }
    }
}
