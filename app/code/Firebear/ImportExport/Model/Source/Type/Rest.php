<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Type;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\DriverPool;

/**
 * Class Rest
 *
 * @package Firebear\ImportExport\Model\Source\Type
 */
class Rest extends AbstractType
{
    /**
     * @var string
     */
    protected $code = 'rest';

    /**
     * @var string
     */
    protected $fileName;

    /**
     * @return string
     */
    public function getTempFilePath()
    {
        $fileName = $this->convertUrlToFilename($this->getData('request_url'));
        return $this->directory->getAbsolutePath($this->getImportPath() . '/' . $fileName);
    }

    /**
     * Download remote source file to temporary directory
     *
     * @return bool|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function uploadSource()
    {
        if ($client = $this->_getSourceClient()) {
            if (!$this->fileName) {
                $filePath = $this->getTempFilePath();
                if (!$this->directory->isExist($filePath)) {
                    $fileMetadata = $client->execute(
                        $this->getData('request_url'),
                        $this->getData('request_method'),
                        $this->convertToFormat($this->getData('request_body')),
                        $this->getHeaders()
                    );
                    if ($fileMetadata && false !== $fileMetadata->response) {
                        $this->directory->writeFile($filePath, (string)$fileMetadata->response);
                        $this->fileName = $filePath;
                    } else {
                        throw new LocalizedException(__('No content from API call, error: ' . $fileMetadata->error));
                    }
                }
            }
            return $this->fileName;
        }
        throw new  LocalizedException(__("Can't initialize %s client", $this->code));
    }

    /**
     * @param $importImage
     * @param $imageSting
     *
     * @return array
     */
    public function importImage($importImage, $imageSting)
    {
        $imageExt = '.jpeg';
        $filePath = $this->directory->getAbsolutePath($this->getMediaImportPath() . $imageSting);
        $dirname = \dirname($filePath);
        if (!mkdir($dirname, 0775, true) && !is_dir($dirname)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dirname));
        }

        if (preg_match('/\bhttps?:\/\//i', $importImage, $matches)) {
            $url = str_replace($matches[0], '', $importImage);
        } else {
            $sourceFilePath = $this->getData($this->code . '_file_path');
            $sourceDir = \dirname($sourceFilePath);
            $url = $sourceDir . '/' . $importImage;
            if (preg_match('/\bhttps?:\/\//i', $url, $matches)) {
                $url = str_replace($matches[0], '', $url);
            }
        }
        if ($url) {
            $fileExistOnServer = 0;
            try {
                $driver = $this->getProperDriverCode($matches);
                $read = $this->readFactory->create($url, $driver);
                $fileExistOnServer = 1;
            } catch (\Exception $e) {
            }
            if ($fileExistOnServer) {
                try {
                    $this->directory->writeFile(
                        $filePath,
                        $read->readAll()
                    );
                    if (\function_exists('mime_content_type')) {
                        $imageExt = $this->getImageMimeType(mime_content_type($filePath));
                        $this->directory->renameFile(
                            $filePath,
                            $filePath . $imageExt
                        );
                    }
                } catch (\Exception $e) {
                }
            }
        }

        return [true, $imageSting . $imageExt];
    }

    protected function getImageMimeType($mime)
    {
        switch ($mime) {
            case 'image/jpeg':
                $ext = '.jpeg';
                break;
            case 'image/png':
                $ext = '.png';
                break;
            default:
                $ext = '.jpeg';
        }
        return $ext;
    }

    public function importImageCategory($importImage, $imageSting)
    {
        $filePath = $this->directory->getAbsolutePath('pub/media/catalog/category/' . $imageSting);
        $dirname = dirname($filePath);
        if (!is_dir($dirname)) {
            mkdir($dirname, 0775, true);
        }
        if (preg_match('/\bhttps?:\/\//i', $importImage, $matches)) {
            $url = str_replace($matches[0], '', $importImage);
        } else {
            $sourceFilePath = $this->getData($this->code . '_file_path');
            $sourceDir = dirname($sourceFilePath);
            $url = $sourceDir . '/' . $importImage;
            if (preg_match('/\bhttps?:\/\//i', $url, $matches)) {
                $url = str_replace($matches[0], '', $url);
            }
        }
        if ($url) {
            try {
                $driver = $this->getProperDriverCode($matches);
                $read = $this->readFactory->create($url, $driver);
                $this->directory->writeFile(
                    $filePath,
                    $read->readAll()
                );
            } catch (\Exception $e) {
            }
        }

        return true;
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
        return true;
    }

    /**
     * Prepare and return Driver client
     *
     * @return \RestClient
     */
    protected function _getSourceClient()
    {
        if (!$this->client) {
            $requestOption = $this->convertToFormat($this->getData('request_options'), 'json', 'object');
            $api = new \RestClient($requestOption);
            $this->client = $api;
        }

        return $this->client;
    }

    /**
     * @param string $data Json request body from Job's settings
     * @param null|string $type
     * @param null $format
     *
     * @return mixed json-structured data
     */
    public function convertToFormat($data, $type = null, $format = null)
    {
        $result = '';
        $type = $type ?: $this->getData('type_file');
        switch ($type) {
            case 'xml':
                $result = $data;
                break;
            case 'json':
                if ($format == 'object') {
                    $result = json_decode($data, true);
                    if (!$result) {
                        $result = [];
                    }
                } else {
                    $result = json_encode(json_decode($data, true));
                }
                break;
            default:
                // ToDo: make an exception and catch it further
                break;
        }
        return $result;
    }

    /**
     * @param $matches
     *
     * @return string
     */
    protected function getProperDriverCode($matches)
    {
        if (is_array($matches)) {
            return (false === strpos($matches[0], 'https'))
                ? DriverPool::HTTP
                : DriverPool::HTTPS;
        } else {
            return DriverPool::HTTP;
        }
    }

    /**
     * @param \Firebear\ImportExport\Model\Export $model
     *
     * @return array
     */
    public function run(\Firebear\ImportExport\Model\Export $model)
    {
        $response = '';
        $result = true;
        $errors = [];
        $path = '';
        try {
            $fileFormat = $model->getFileFormat();
            $this->setExportModel($model);
            $name = 'export_' . $this->timezone->date()->format('Y_m_d_H_i_s') .
                '.' . $fileFormat;
            $path = AbstractType::EXPORT_DIR . '/' . $name;
            if ($this->writeFile($path)) {
                if ($client = $this->_getSourceClient()) {
                    $this->setData('type_file', $fileFormat);
                    $currentDate = '';
                    if ($this->getData('date_format')) {
                        $format = $this->getData('date_format') ?? 'Y-m-d-hi';
                        $currentDate = '-' . $this->timezone->date()->format($format);
                    }

                    $filePath = $this->directory->getAbsolutePath($path);
                    $dataRead = $this->readFactory->create($filePath, DriverPool::FILE);
                    $headers = $this->getHeaders();
                    try {
                        if ($this->getData('request_method') === 'DELETE') {
                            $result = $client->delete($this->getData('request_url'), $dataRead->readAll(), $headers);
                        } else {
                            $result = $client->put($this->getData('request_url'), $dataRead->readAll(), $headers);
                        }
                    } catch (\TypeError  $error) {
                        $result = false;
                    }
                    if (!$result) {
                        $result = false;
                        $errors[] = __('REST API: The data was not processed. Please check the REST API data.');
                    } else {
                        $response = $result->response;
                    }
                } else {
                    $result = false;
                    $errors[] = __("Can't initialize %s client", $this->code);
                }
            } else {
                $result = false;
            }
        } catch (\Exception $e) {
            $result = false;
            $errors[] = __('Folder for import / export don\'t have enough permissions! Please set 775');
        }

        return [$result, $path, $errors, $response];
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return [];
    }
}
