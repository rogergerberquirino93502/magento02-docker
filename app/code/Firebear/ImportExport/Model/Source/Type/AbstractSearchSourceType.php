<?php
declare(strict_types=1);
/**
 * AbstractSearchSourceType
 *
 * @copyright Copyright © 2020 Firebear Studio. All rights reserved.
 * @author    fbeardev@gmail.com
 */

namespace Firebear\ImportExport\Model\Source\Type;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class AbstractSearchSourceType
 * @package Firebear\ImportExport\Model\Source\Type
 */
abstract class AbstractSearchSourceType extends AbstractType implements SearchSourceTypeInterface
{
    /**
     * @param array $searchedFiles
     * @return array
     */
    public function filterSearchedFiles(array &$searchedFiles)
    {
        foreach ($searchedFiles as $key => $importFile) {
            if (is_array($importFile)) {
                unset($searchedFiles[$key]);
                continue;
            }
            if (in_array($importFile, $searchedFiles)) {
                if (strpos($importFile, '_xslt') !== false) {
                    $this->removeFile($importFile);
                    unset($searchedFiles[$key]);
                }
            }
        }
        return $searchedFiles;
    }

    /**
     * Remove file from the source location for FTP or SFTP
     * @param string $fileName
     * @return mixed
     */
    protected function _removeFile(string $fileName)
    {
        return $this->_getSourceClient()->rm($fileName);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getConfigFilePath()
    {
        $filePath = $this->getData($this->code . '_file_path') ?: $this->getData('file_path') ?: '';
        return dirname($filePath);
    }

    /**
     * @param $importFile
     * @return false|string
     * @throws FileSystemException
     * @throws LocalizedException
     */
    protected function getFile($importFile)
    {
        $this->directory->create($this->getImportPath() . DIRECTORY_SEPARATOR . dirname($importFile));
        $dest = $this->getLocalTarget($importFile);
        return $this->_getSourceClient()->read($importFile, $dest);
    }

    /**
     * @param $importFile
     * @return string
     */
    protected function getLocalTarget($importFile)
    {
        return $this->directory->getAbsolutePath($this->getImportPath() . DIRECTORY_SEPARATOR . $importFile);
    }

    /**
     * @param string $source
     * @param string $target
     * @throws LocalizedException
     */
    protected function _moveFile(string $source, string $target)
    {
        try {
            if (!$this->_getSourceClient()->mkdir(dirname($target))) {
                throw new LocalizedException(__('Error creating a directory on the server - %1', basename($target)));
            }
            $localTarget = $this->getLocalTarget($target);
            $localSource = $this->getLocalTarget($source);
            if (!$this->directory->isExist($localTarget)) {
                $this->directory->create(dirname($localTarget));
            }

            if (!$this->directory->isExist($localTarget)) {
                $this->directory->renameFile($localSource, $localTarget);
            }
        } catch (\Exception $exception) {
            throw new LocalizedException(__($exception->getMessage()));
        }

        $this->_getSourceClient()->rm($target);
        return $this->_getSourceClient()->mv($source, $target);
    }

    /**
     * @return bool
     */
    public function isSearchable()
    {
        return true;
    }

    /**
     * @param string $dirName
     * @return string|void
     */
    public function getDirName(string $dirName = self::COM_DIR)
    {
        return DIRECTORY_SEPARATOR . $dirName . DIRECTORY_SEPARATOR;
    }

    /**
     * @param array $files
     * @return array
     */
    protected function formattingSearchResult(array $files)
    {
        return array_map(
            function ($v) {
                if (is_array($v)) {
                    return $v['id'] ?? '';
                }
                return $v;
            },
            $files
        );
    }

    /**
     * @param array $fileList
     * @param $extensionMask
     * @return array
     */
    protected function grep(array $fileList, $extensionMask)
    {
        $list = [];
        foreach ($fileList as $file) {
            $fileExtension = pathinfo($file, PATHINFO_EXTENSION);
            if ($fileExtension == $extensionMask) {
                $list[] = $file;
            }
        }
        return $list;
    }

    /**
     * @return array
     */
    public function allowedDirsForScan()
    {
        return [];
    }

    /**
     * @inheridoc
     */
    public function isAllowablePath(string $path)
    {
        return true;
    }
}
