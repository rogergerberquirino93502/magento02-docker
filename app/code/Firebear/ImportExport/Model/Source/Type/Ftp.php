<?php
declare(strict_types=1);
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Type;

use Exception;
use Firebear\ImportExport\Api\Export\History\CompressInterface;
use Firebear\ImportExport\Model\Source\Factory as SourceFactory;
use Firebear\ImportExport\Model\Filesystem\File\ReadFactory;
use Firebear\ImportExport\Model\Filesystem\Io\Ftp as FtpIo;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Directory\WriteFactory as DirectoryWriteFactory;
use Magento\Framework\Filesystem\File\WriteFactory as FileWriteFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Stdlib\DateTime\Timezone;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Ftp
 *
 * @package Firebear\ImportExport\Model\Source\Type
 */
class Ftp extends AbstractSearchSourceType
{
    /**
     * @var string
     */
    protected $code = 'ftp';

    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $file;

    /**
     * @var \Firebear\ImportExport\Model\Filesystem\Io\Ftp
     */
    protected $ftp;

    /**
     * Ftp constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Filesystem $filesystem
     * @param ReadFactory $readFactory
     * @param DirectoryWriteFactory $writeFactory
     * @param FileWriteFactory $fileWrite
     * @param Timezone $timezone
     * @param SourceFactory $factory
     * @param CacheInterface $cache
     * @param CompressInterface $compressCommand
     * @param \Magento\Framework\Filesystem\Io\File $file
     * @param FtpIo $ftp
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
        \Magento\Framework\Filesystem\Io\File $file,
        FtpIo $ftp
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
            $compressCommand
        );
        $this->file = $file;
        $this->ftp = $ftp;
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
     * Download remote source file to temporary directory
     *
     * @return string
     * @throws LocalizedException
     */
    public function uploadSource()
    {
        if ($client = $this->_getSourceClient()) {
            $filePath = $this->getTempFilePath();
            if (!$this->directory->isExist($filePath)) {
                $remoteFilePath = $this->getData($this->code . '_file_path');
                $this->directory->create(dirname($filePath));
                $result = $client->read($remoteFilePath, $filePath);
                if (!$result) {
                    throw new LocalizedException(__("File not found"));
                }
            }
            return $filePath;
        }
        throw new  LocalizedException(__("Can't initialize %s client", $this->code));
    }

    /**
     * Download remote images to temporary media directory
     *
     * @param $importImage
     * @param $imageSting
     * @throws LocalizedException
     */
    public function importImage($importImage, $imageSting)
    {
        if ($client = $this->_getSourceClient()) {
            $filePath = $this->directory->getAbsolutePath($this->getMediaImportPath() . $imageSting);
            $dirname = dirname($filePath);
            if (!$this->directory->isExist($dirname)) {
                $this->directory->create($dirname);
            }

            $sourceDir = $this->getData('remote_images_file_dir');
            if ($sourceDir !== '') {
                $importImage = '/' . trim($sourceDir, '/') . '/' . $importImage;
            }

            if ($client->checkIsPath($importImage, $filePath)) {
                $result = $client->read($importImage, $filePath);
            }
        }
    }

    /**
     * Check if remote file was modified since the last import
     *
     * @param int $timestamp
     * @return bool|int
     */
    public function checkModified($timestamp)
    {
        if ($client = $this->_getSourceClient()) {
            $sourceFilePath = $this->getData($this->code . '_file_path');

            if (!$this->metadata) {
                $this->metadata['modified'] = $client->mdtm($sourceFilePath);
            }

            $modified = $this->metadata['modified'];

            return ($timestamp != $this->metadata['modified']) ? $modified : false;
        }

        return false;
    }

    /**
     * Prepare and return FTP client
     *
     * @return \Firebear\ImportExport\Model\Filesystem\Io\Ftp
     * @throws LocalizedException
     */
    protected function _getSourceClient()
    {
        if (!$this->getClient()) {
            if ($this->getData('host') && $this->getData('port')
                && $this->getData('user') && $this->getData('password')) {
                $settings = $this->getData();
            } else {
                $settings = $this->scopeConfig->getValue(
                    'firebear_importexport/ftp',
                    ScopeInterface::SCOPE_STORE
                );
            }

            $settings['passive'] = true;
            try {
                $connection = $this->ftp;
                $connection->open(
                    $settings
                );
                $this->client = $connection;
            } catch (Exception $e) {
                throw new  LocalizedException(__($e->getMessage()));
            }
        }

        return $this->getClient();
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
            $info = pathinfo($this->getData('file_path'));
            $this->setExportModel($model);
            $name = 'export_' . $this->timezone->date()->format('Y_m_d_H_i_s_') . $info['basename'];
            $path = AbstractType::EXPORT_DIR . "/" . $name;
            if ($this->writeFile($path)) {
                if ($client = $this->_getSourceClient()) {
                    $fileFormat = $model->getFileFormat();
                    $currentDate = "";
                    if ($this->getData('date_format')) {
                        $format = $this->getData('date_format') ?? 'Y-m-d-hi';
                        $currentDate = "-" . $this->timezone->date()->format($format);
                    }
                    $filePath = $this->directory->getAbsolutePath($path);
                    $destFilePath = $info['dirname'] . DIRECTORY_SEPARATOR;
                    $destFileName = $info['filename'] . $currentDate . '.' . $info['extension'];
                    $client->mkdir($destFilePath, 0775, true);
                    $result = $client->write($destFileName, $filePath);
                    if (!$result) {
                        $result = false;
                        $errors[] = __('File not found');
                    }
                    $client->close();
                } else {
                    $result = false;
                    $errors[] = __("Can't initialize %s client", $this->code);
                }
            }
        } catch (Exception $e) {
            $result = false;
            $errors[] = __('Folder for import / export don\'t have enough permissions! Please set 775');
        }

        return [$result, $path, $errors];
    }

    /**
     * @param $fileName
     * @return bool
     * @throws LocalizedException
     */
    public function removeFile(string $fileName)
    {
        return $this->_getSourceClient()->rm($fileName);
    }

    /**
     * @param string $fileSearchRegex
     * @return array
     * @throws LocalizedException
     */
    public function search(string $fileSearchRegex)
    {
        $client = $this->_getSourceClient();
        $client->cd($this->getImportFilePath());
        $fileList = $client->search();
        if (is_array($fileList)) {
            $fileList = $this->formattingSearchResult($fileList);
            $exstension = pathinfo($fileSearchRegex, PATHINFO_EXTENSION);
            $fileList = $this->grep($fileList, $exstension);
        }
        return $fileList;
    }

    /**
     * Move file from current directory to targeted directory
     *
     * @param string $source
     * @param string $target
     * @return bool
     */
    public function move(string $source, string $target)
    {
        return $this->_moveFile($source, $target);
    }

    /**
     * @param string $importFile
     * @return string
     */
    public function getFilePath(string $importFile)
    {
        $this->setData($this->getCode() . '_file_path', $importFile);
        $this->getFile($importFile);
        return $importFile;
    }

    /**
     * @return bool
     * @throws LocalizedException
     */
    public function deleteFile()
    {
        $result = false;
        if ($client = $this->_getSourceClient()) {
            $result = $client->rm($this->getImportFilePath());
        }
        return $result;
    }

    /**
     * @return bool
     */
    public function isAllowedDelete()
    {
        return true;
    }

    /**
     * @inheridoc
     */
    public function isExists(string $path)
    {
        return $this->_getSourceClient()->cd($path);
    }
}
