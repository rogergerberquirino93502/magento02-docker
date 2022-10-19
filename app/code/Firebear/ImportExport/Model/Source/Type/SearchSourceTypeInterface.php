<?php
declare(strict_types=1);
/**
 * SearchSourceTypeInterface
 *
 * @copyright Copyright © 2020 Firebear Studio. All rights reserved.
 * @author    fbeardev@gmail.com
 */

namespace Firebear\ImportExport\Model\Source\Type;

use Magento\Framework\Exception\FileSystemException;

interface SearchSourceTypeInterface extends SourceTypeInterface
{
    const SCAN_DIR = 'scan_directory';
    const COM_DIR = 'complete';
    const ERR_DIR = 'error';

    /**
     * @param string $fileSearchRegex
     * @return array
     */
    public function search(string $fileSearchRegex);

    /**
     * @param string $fileName
     * @return bool
     */
    public function removeFile(string $fileName);

    /**
     * Get the code of current import type.
     * @return string
     */
    public function getCode();

    /**
     * Move file from current directory to targeted directory
     *
     * @param string $source
     * @param string $target
     * @return bool
     * @throws FileSystemException
     */
    public function move(string $source, string $target);

    /**
     * Returns configured filepath for FTP|SFTP|File
     * to transfer the files to correct folders when it
     * complete or error
     * @return string
     */
    public function getConfigFilePath();

    /**
     * To filter out _xslt* files for XML so that it doesn't get processed and break the import.
     *
     * @param array $searchedFiles
     * @return array
     */
    public function filterSearchedFiles(array &$searchedFiles);

    /**
     * @param string $importFile
     * @return string
     */
    public function getFilePath(string $importFile);

    /**
     * @param string $dirName
     * @return string
     */
    public function getDirName(string $dirName = self::COM_DIR);

    /**
     * @param string $path
     * @return bool
     */
    public function isExists(string $path);

    /**
     * @param string $path
     * @return bool
     */
    public function isAllowablePath(string $path);
}
