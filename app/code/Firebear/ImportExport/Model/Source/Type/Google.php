<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Type;

use Magento\Framework\Filesystem\DriverPool;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class Url
 * @package Firebear\ImportExport\Model\Source\Type
 */
class Google extends AbstractType
{
    /**
     * @var string
     */
    protected $code = 'google';

    /**
     * @var string
     */
    protected $fileName;

    /**
     * @return string
     */
    public function getTempFilePath()
    {
        $fileName = $this->getFileName() . ".csv";
        return $this->directory->getAbsolutePath($this->getImportPath() . '/' . $fileName);
    }

    /**
     * Download remote source file to temporary directory
     *
     * @return bool|mixed|string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function uploadSource()
    {
        $filePath = $this->getTempFilePath();
        if (!$this->directory->isExist($filePath)) {
            $read = $this->_getSourceClient();
            if (!$read) {
                return false;
            }
            $this->directory->writeFile($filePath, $read->readAll());
        }
        return $filePath;
    }

    /**
     * @return string
     */
    protected function getFileName(): string
    {
        return str_replace('&', '', $this->generationStemName());
    }

    /**
     * @return string
     */
    protected function generationStemName(): string
    {
        $stemName = $this->getData($this->code . '_file_path');
        if (preg_match('/\bhttps?:\/\//i', $stemName, $matches)) {
            $stemName = str_replace($matches[0], '', $stemName);
        }
        return $stemName;
    }

    /**
     * Download remote images to temporary media directory
     *
     * @param $importImage
     * @param $imageSting
     * @return bool
     */
    public function importImage($importImage, $imageSting)
    {
        $filePath = $this->directory->getAbsolutePath($this->getMediaImportPath() . $imageSting);
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
                $read = $this->readFactory->create($url, DriverPool::HTTP);
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
     * @return bool|int
     */
    public function checkModified($timestamp)
    {
        $fileName = $this->getData($this->code . '_file_path');
        if (preg_match('/\bhttps?:\/\//i', $fileName, $matches)) {
            $url = str_replace($matches[0], '', $fileName);

            $read = $this->readFactory->create($url, DriverPool::HTTP);

            if (!$this->metadata) {
                $this->metadata = $read->stat();
            }

            $modified = strtotime($this->metadata['mtime']);

            return ($timestamp != $modified) ? $modified : false;
        }

        return false;
    }

    /**
     * Prepare and return Driver client
     *
     * @return \Magento\Framework\Filesystem\File\ReadInterface
     */
    protected function _getSourceClient()
    {
        if (!$this->client) {
            $this->client = $this->readFactory->create($this->generationStemName(), DriverPool::HTTP);
        }
        return $this->client;
    }

    /**
     * @inheritdoc
     */
    public function run($model)
    {
        $result = true;
        $errors = [];

        try {
            $this->setExportModel($model);
            $this->getExportModel()->export();
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
            if (empty($errors)) {
                $errors[] = __('No products with tier prices in catalog found.');
            }

            $result = false;
        }

        return [$result, '', $errors];
    }
}
