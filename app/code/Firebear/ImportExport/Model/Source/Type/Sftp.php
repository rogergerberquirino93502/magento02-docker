<?php
/**
 * @copyright: Copyright © 2015 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Type;

use Exception;
use Firebear\ImportExport\Model\Filesystem\Io\Sftp as IoSftp;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Sftp
 *
 * @package Firebear\ImportExport\Model\Source\Type
 */
class Sftp extends AbstractSearchSourceType
{
    const SFTP_SOURCE = IoSftp::class;

    /**
     * @var string
     */
    protected $code = 'sftp';

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
        throw new LocalizedException(__("Can't initialize %s client", $this->code));
    }

    /**
     * Prepare and return SFTP client
     *
     * @return IoSftp
     * @throws LocalizedException
     */
    protected function _getSourceClient()
    {
        if (!$this->getClient()) {
            if ($this->getData('host')
                && $this->getData('port')
                && $this->getData('username')
                && $this->getData('password')) {
                $settings = $this->getData();
            } else {
                $settings = $this->scopeConfig->getValue(
                    'firebear_importexport/sftp',
                    ScopeInterface::SCOPE_STORE
                );
            }
            $settings['passive'] = true;
            try {
                $connection = $this->factory->create(self::SFTP_SOURCE);
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
            $client->read($importImage, $filePath);
        }
    }

    /**
     * Check if remote file was modified since the last import
     *
     * @param int $timestamp
     * @return bool|int
     * @throws LocalizedException
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
                    $sourceFilePath = $info['dirname'] . '/' . $info['filename'] .
                        $currentDate . '.' . $info['extension'];

                    $filePath = $this->directory->getAbsolutePath($path);
                    $client->mkdir($info['dirname']);
                    $result = $client->write($sourceFilePath, $filePath);
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
     * @throws LocalizedException
     */
    public function move(string $source, string $target)
    {
        return $this->_moveFile($source, $target);
    }

    /**
     * @param string $importFile
     * @return string
     * @throws FileSystemException
     * @throws LocalizedException
     */
    public function getFilePath(string $importFile)
    {
        $this->setData($this->code . '_file_path', $importFile);
        $this->getFile($importFile);
        return $importFile;
    }

    /**
     * @param string $fileName
     * @return bool
     */
    public function removeFile(string $fileName)
    {
        return $this->_removeFile($fileName);
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
