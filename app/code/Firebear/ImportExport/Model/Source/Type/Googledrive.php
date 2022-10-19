<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Type;

use Magento\Framework\Exception\LocalizedException;

/**
 * Class Googledrive
 * @package Firebear\ImportExport\Model\Source\Type
 */
class Googledrive extends AbstractType
{
    const FOLDER_MIME_TYPE = 'application/vnd.google-apps.folder';
    const FILE_MIME_TYPE = 'application/octet-stream';

    /**
     * @var string
     */
    protected $code = 'googledrive';

    protected $service;

    /**
     * @var null
     */
    protected $accessToken = null;

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
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function uploadSource()
    {
        if ($client = $this->_getSourceClient()) {
            $filePath = $this->getTempFilePath();
            if (!$this->directory->isExist($filePath)) {
                $remoteFilePath = $this->getData($this->code . '_file_path');
                $this->directory->create(dirname($filePath));

                $file = $this->_getFileByPath($remoteFilePath);
                if (!$file) {
                    throw new LocalizedException(__("File not found on Google Drive"));
                }

                $service = $this->_getSourceService();
                $fileMetadata = $service->files->get($file->id, ['alt' => 'media']);

                if ($fileMetadata) {
                    $this->directory->writeFile($filePath, $fileMetadata->getBody()->getContents());
                } else {
                    throw new LocalizedException(__("No metadata from file"));
                }
            }
            return $filePath;
        }
        throw new LocalizedException(__("Can't initialize %s client", $this->code));
    }

    /**
     * @param $importImage
     * @param $imageSting
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function importImage($importImage, $imageSting)
    {
        if ($client = $this->_getSourceClient()) {
            if (preg_match('/\bhttps?:\/\//i', $importImage, $matches)) {
                $this->setUrl($importImage, $imageSting, $matches);
            } else {
                $filePath = $this->directory->getAbsolutePath($this->getMediaImportPath() . $imageSting);
                $dirname = dirname($filePath);
                if (!$this->directory->isExist($dirname)) {
                    $this->directory->create($dirname);
                }

                $sourceDir = $this->getData($this->code . '_import_images_file_dir');
                if ($sourceDir !== '') {
                    $importImage = trim($sourceDir, '/') . '/' . $importImage;
                }

                $image = $this->_getFileByPath($importImage);
                if (!$image) {
                    throw new LocalizedException(__("Image not found on Google Drive"));
                }

                $service = $this->_getSourceService();
                $metadata = $service->files->get($image->id, ['alt' => 'media']);
                if ($metadata) {
                    $this->directory->writeFile($filePath, $metadata->getBody()->getContents());
                } else {
                    throw new LocalizedException(__("No metadata from image"));
                }
            }
        }
    }

    /**
     * Check if remote file was modified since the last import
     *
     * @param int $timestamp
     *
     * @return bool|int
     */
    public function checkModified($timestamp)
    {
        if ($client = $this->_getSourceClient()) {
            $sourceFilePath = $this->getData($this->code . '_file_path');

            $file = $this->_getFileByPath($sourceFilePath);

            if ($file) {
                $modified = strtotime($file->modifiedTime);
                return ($timestamp != $modified) ? $modified : false;
            }
        }

        return false;
    }

    /**
     * Set access token
     *
     * @param $token
     */
    public function setAccessToken($token)
    {
        $this->accessToken = $token;
    }

    /**
     * @param $model
     * @return null|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function run($model)
    {
        $result = true;
        $errors = [];
        $path = '';
        $file = null;

        try {
            $this->setExportModel($model);
            $name = 'export_' . $this->timezone->date()->format('Y_m_d_H_i_s');
            $path = AbstractType::EXPORT_DIR . "/" . $name;
            if ($this->writeFile($path)) {
                if ($client = $this->_getSourceClient()) {
                    $currentDate = "";
                    if ($this->getData('date_format')) {
                        $format = $this->getData('date_format') ?? 'Y-m-d-hi';
                        $currentDate = "-" . $this->timezone->date()->format($format);
                    }
                    $info = pathinfo($this->getData('file_path'));
                    $fileName = $info['filename'] . $currentDate . '.' . $info['extension'];
                    $filePath = $this->directory->getAbsolutePath($path);
                    $folder = $this->_createFolderByPath($info['dirname']);
                    if ($folder) {
                        $file = $this->_createFile($fileName, self::FILE_MIME_TYPE, $folder->id, $filePath);
                        if (!$file) {
                            $result = false;
                            $errors[] = __('The export file was not created. Please, try again');
                        }
                    } else {
                        $result = false;
                        $errors[] = __('Folder not found. Please check folder permissions');
                    }
                } else {
                    $result = false;
                    $errors[] = __("Can't initialize %s client", $this->code);
                }
            }
        } catch (\Exception $e) {
            $result = false;
            $errors[] = __('Some errors occurred during the export file to Google Drive. Please, try again');
        }

        return [$result, $path, $errors];
    }

    /**
     * @return \Google_Client
     * @throws \Exception
     */
    protected function _getSourceClient()
    {
        if (!$this->client) {
            $signingKeyFilePath = $this->directory->getAbsolutePath() . $this->getData('signing_key_file_path');

            if (is_string($signingKeyFilePath)) {
                if (!is_readable($signingKeyFilePath)) {
                    $errorMessage = 'The Service Account Key file does not exist or does not have enough permissions';
                    throw new LocalizedException(__($errorMessage));
                }
                $signingKeyStream = file_get_contents($signingKeyFilePath);
                if (!$signingKey = json_decode($signingKeyStream, true)) {
                    throw new \LogicException('The Service Account Key json is invalid');
                }
                $credentials = [
                    'use_application_default_credentials' => true,
                    'client_id' => $signingKey['client_id'],
                    'client_email' => $signingKey['client_email'],
                    'signing_key' => $signingKey['private_key']
                ];
                $client = new \Google_Client($credentials);
                $client->addScope("https://www.googleapis.com/auth/drive");
                $this->client = $client;
            }
        }

        return $this->client;
    }

    /**
     * @return \Google_Service_Drive
     * @throws \Exception
     */
    protected function _getSourceService()
    {
        if (!$this->service) {
            if (!$this->client) {
                $this->client = $this->_getSourceClient();
            }
            $service = new \Google_Service_Drive($this->client);
            $this->service = $service;
        }

        return $this->service;
    }

    /**
     * @param $path
     * @return null
     * @throws \Exception
     */
    protected function _getFileByPath($path)
    {
        $file = null;
        $parentFolder = null;
        $filePathInfo = explode('/', trim($path, '/'));
        $fileName = trim(array_pop($filePathInfo));

        if (!empty($filePathInfo)) {
            foreach ($filePathInfo as $folder) {
                $parentFolder = $this->_getFolder($folder, $parentFolder);
            }

            if ($parentFolder) {
                $query = "name='" . $fileName . "' and parents in '" . $parentFolder->id . "'";
                $file = $this->_getFile($query);
            }
        } else {
            $query = "name='" . $fileName . "'";
            $file = $this->_getFile($query);
        }
        return $file;
    }

    /**
     * @param $name
     * @param null $parentFolder
     * @return null
     * @throws \Exception
     */
    protected function _getFolder($name, $parentFolder = null)
    {
        $query = "mimeType='" . self::FOLDER_MIME_TYPE ."' and name='" . $name . "'";

        if ($parentFolder) {
            $query = $query . " and parents in '" . $parentFolder->id . "'";
        }

        $folder = $this->_getFile($query);
        return $folder;
    }

    /**
     * @param $query
     * @return null
     * @throws \Exception
     */
    protected function _getFile($query)
    {
        $file = null;
        $pageToken = null;
        $service = $this->_getSourceService();
        do {
            $response = $service->files->listFiles([
                'q' => $query,
                'pageToken' => $pageToken,
                'fields' => 'nextPageToken, files(id, name, modifiedTime)',
            ]);
            foreach ($response->files as $file) {
                $file = $file;
            }

            $pageToken = $response->pageToken;
        } while ($pageToken != null);

        return $file;
    }

    /**
     * @param $name
     * @param $mimeType
     * @param null $parentFolderId
     * @param null $filePath
     * @return mixed
     * @throws \Exception
     */
    protected function _createFile($name, $mimeType, $parentFolderId = null, $filePath = null)
    {
        $service = $this->_getSourceService();
        $params = [
            'fields' => 'id'
        ];

        if ($filePath) {
            $params['data'] = file_get_contents($filePath);
            $params['uploadType'] = 'multipart';
        }

        $fileMetadataParams = [
            'name' => $name,
            'mimeType' => $mimeType
        ];

        if ($parentFolderId) {
            $fileMetadataParams['parents'] = [$parentFolderId];
        }

        $fileMetadata  = new \Google_Service_Drive_DriveFile($fileMetadataParams);

        $result = $service->files->create(
            $fileMetadata,
            $params
        );

        return $result;
    }

    /**
     * @param $path
     * @return mixed|null
     * @throws \Exception
     */
    protected function _createFolderByPath($path)
    {
        $folderPathInfo = explode('/', trim($path, '/'));
        $folder = null;
        $parentFolderId = null;
        $parentFolderName = array_shift($folderPathInfo);
        $parentFolder = $this->_getFolder($parentFolderName, null);

        if ($parentFolder) {
            $folder = $parentFolder;
            $parentFolderId = $parentFolder->id;

            foreach ($folderPathInfo as $folderName) {
                $folder = $this->_getFolder($folderName, $folder);
                if (!$folder) {
                    $folder = $this->_createFile($folderName, self::FOLDER_MIME_TYPE, $parentFolderId);
                }
                if ($folder) {
                    $parentFolderId = $folder->id;
                }
            }
        }

        return $folder;
    }
}
