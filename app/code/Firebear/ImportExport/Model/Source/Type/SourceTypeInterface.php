<?php
declare(strict_types=1);
/**
 * SourceTypeInterface
 *
 * @copyright Copyright © 2020 Firebear Studio. All rights reserved.
 * @author    fbeardev@gmail.com
 */

namespace Firebear\ImportExport\Model\Source\Type;

use Magento\Framework\Exception\FileSystemException;

/**
 * Interface SourceTypeInterface
 * @package Firebear\ImportExport\Model\Source\Type
 */
interface SourceTypeInterface
{
    /**
     * Temp directory for downloaded files
     */
    const IMPORT_DIR = 'var/import';

    /**
     * Temp directory for downloaded images
     */
    const MEDIA_IMPORT_DIR = 'pub/media/import';

    /**
     * Export directory
     */
    const EXPORT_DIR = 'var/export';

    /**
     * Get current filepath of the import File.
     *
     * @return string
     */
    public function getImportFilePath();

    /**
     * Get source type code
     *
     * @return string
     */
    public function getCode();

    /**
     * @return mixed
     */
    public function getClient();

    /**
     * @param $client
     */
    public function setClient($client);

    /**
     * @return mixed
     */
    public function uploadSource();

    /**
     * @param $importImage
     * @param $imageSting
     * @return mixed
     */
    public function importImage($importImage, $imageSting);

    /**
     * @param $timestamp
     * @return mixed
     */
    public function checkModified($timestamp);

    /**
     * @return bool
     */
    public function check();

    /**
     * @param string $importImage
     * @param string $imageSting
     * @param array $matches
     * @throws FileSystemException
     */
    public function setUrl($importImage, $imageSting, $matches);

    /**
     * @return string
     */
    public function getFormatFile();

    /**
     * @param string $file
     * @return $this
     */
    public function setFormatFile($file);

    /**
     * @param string $url
     *
     * @return string
     */
    public function convertUrlToFilename($url);

    /**
     * @return mixed
     */
    public function getExportModel();

    /**
     * @param $model
     */
    public function setExportModel($model);

    /**
     * @return bool
     */
    public function isSearchable();
}
