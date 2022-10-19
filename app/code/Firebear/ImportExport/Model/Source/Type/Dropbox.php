<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Type;

use Magento\Framework\Exception\LocalizedException;

/**
 * Class Dropbox
 *
 * @package Firebear\ImportExport\Model\Source\Type
 */
class Dropbox extends AbstractType
{
    /**
     * @var string
     */
    protected $code = 'dropbox';

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
     * @throws LocalizedException
     */
    public function uploadSource()
    {
        $filePath = $this->getTempFilePath();
        if (!$this->directory->isExist($filePath)) {
            $remoteFilePath = $this->getData($this->code . '_file_path');
            $remoteFilePath = '/' . trim($remoteFilePath, '/');
            $this->directory->create(dirname($filePath));

            $fileContent = $this->downloadFile($remoteFilePath);
            if (!$fileContent) {
                throw new LocalizedException(__("File not found on Dropbox"));
            }
            $this->directory->writeFile($filePath, $fileContent);
        }
        return $filePath;
    }

    /**
     * @param $importImage
     * @param $imageSting
     * @throws LocalizedException
     */
    public function importImage($importImage, $imageSting)
    {
        if (preg_match('/\bhttps?:\/\//i', $importImage, $matches)) {
            $this->setUrl($importImage, $imageSting, $matches);
        } else {
            $filePath = $this->directory->getAbsolutePath($this->getMediaImportPath() . $imageSting);
            $dirname = dirname($filePath);
            if (!$this->directory->isExist($dirname)) {
                $this->directory->create($dirname);
            }

            $sourceDir = $this->getData('remote_images_file_dir');
            if ($sourceDir !== '') {
                $importImage = '/' . trim($sourceDir, '/') . '/' . $importImage;
            }

            $fileContent = $this->downloadFile($importImage);
            $this->directory->writeFile($filePath, $fileContent);
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

        $sourceFilePath = $this->getData($this->code . '_file_path');

        if (!$this->metadata) {
            $this->metadata = $this->getMetadata($sourceFilePath);
        }

        $modified = strtotime($this->metadata['client_modified']);

        return ($timestamp != $modified) ? $modified : false;
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
     * @return bool
     */
    protected function _getSourceClient()
    {
        $this->client = false;
        return $this->client;
    }

    /**
     * @return bool
     */
    public function isRemote()
    {
        return true;
    }

    /**
     * Get file content from dropbox
     *
     * @param $filePath
     *
     * @return bool|mixed
     * @throws LocalizedException
     */
    protected function downloadFile($filePath)
    {
        $url = 'https://content.dropboxapi.com/2/files/download';

        $resource = curl_init($url);

        curl_setopt($resource, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->getData('access_token'),
            'Content-Type: application/octet-stream',
            'Dropbox-API-Arg: {"path": "' . $filePath . '"}'
        ]);
        curl_setopt($resource, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($resource, CURLOPT_FOLLOWLOCATION, 1);
        $result = curl_exec($resource);
        curl_close($resource);

        if ($json = json_decode($result, true)) {
            if (!empty($json['error']['.tag'])) {
                $tag = $json['error']['.tag'];
                if ($tag == 'invalid_access_token') {
                    $error = "Invalid Dropbox access token";
                } elseif ($tag == 'path') {
                    $error = "File not found on Dropbox: " . $filePath;
                } else {
                    $error = "Dropbox api error: " . $result;
                }
                throw new LocalizedException(__($error));
            }
        }

        if ($result) {
            return $result;
        }

        return false;
    }

    /**
     * Get file metadata
     *
     * @param $filePath
     *
     * @return bool|mixed
     */
    protected function getMetadata($filePath)
    {
        $url = 'https://api.dropboxapi.com/2/files/get_metadata';

        $resource = curl_init($url);

        curl_setopt($resource, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->getData('access_token'),
            'Content-Type: application/json',
        ]);
        curl_setopt($resource, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($resource, CURLOPT_POST, true);
        curl_setopt($resource, CURLOPT_POSTFIELDS, '{"path": "' . $filePath . '"}');
        curl_setopt($resource, CURLOPT_FOLLOWLOCATION, 1);
        $result = curl_exec($resource);
        curl_close($resource);

        if ($result) {
            return json_decode($result, true);
        }

        return false;
    }
}
