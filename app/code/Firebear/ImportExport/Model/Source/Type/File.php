<?php
declare(strict_types=1);

namespace Firebear\ImportExport\Model\Source\Type;

use Exception;
use Firebear\ImportExport\Api\Export\History\CompressInterface;
use Firebear\ImportExport\Model\ExportJob\Processor;
use Firebear\ImportExport\Model\Filesystem\File\ReadFactory;
use Firebear\ImportExport\Model\Source\Factory as SourceFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteFactory as DirectoryWriteFactory;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Filesystem\File\WriteFactory as FileWriteFactory;
use Magento\Framework\Stdlib\DateTime\Timezone;

/**
 * Class File
 *
 * @package Firebear\ImportExport\Model\Source\Type
 */
class File extends AbstractSearchSourceType
{
    /**
     * @var string
     */
    protected $code = 'file';

    /**
     * @var FileDriver
     */
    protected $fileDriver;

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
        FileDriver $fileDriver,
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
        $this->fileDriver = $fileDriver;
    }

    /**
     * Get file path
     *
     * @return bool|string
     */
    public function getImportFilePath()
    {
        if ($filePath = $this->getData('file_path')) {
            return $filePath;
        }
        return false;
    }

    /**
     * @return string
     */
    public function getTempFilePath()
    {
        return '';
    }

    /**
     * Remove uploaded temporary file
     *
     * @return bool
     */
    public function resetSource($resetSourceFlag = false)
    {
        return false;
    }

    /**
     * @return null
     */
    public function uploadSource()
    {
        return null;
    }

    /**
     * @param $importImage
     * @param $imageSting
     *
     * @return null
     */
    public function importImage($importImage, $imageSting)
    {
        return null;
    }

    /**
     * @param $timestamp
     *
     * @return null
     */
    public function checkModified($timestamp)
    {
        return null;
    }

    /**
     * @return WriteInterface
     */
    protected function _getSourceClient()
    {
        return $this->directory;
    }

    /**
     * @param $model
     * @return array
     */
    public function run($model)
    {
        $result = true;
        $errors = [];
        $file = '';
        try {
            $this->setExportModel($model);
            $data = $model->getData(Processor::EXPORT_SOURCE);
            $currentDate = "";
            if ($data['date_format']) {
                $format = $data['date_format'] ?? 'Y-m-d-hi';
                $currentDate = "-" . $this->timezone->date()->format($format);
            }
            $info = pathinfo($data['file_path']);
            $file = $info['dirname'] . '/' . $info['filename'] . $currentDate;
            if (isset($info['extension'])) {
                $file .= '.' . $info['extension'];
            }
            $file = $this->prepareFilePath($file);
            $file = $this->writeFile($file);
            if (false === $file) {
                $result = false;
            }
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
            if (empty($errors)) {
                $errors[] = __('No products with tier prices in catalog found.');
            }
            $result = false;
        }

        return [$result, $file, $errors];
    }

    /**
     * @param $path
     * @return mixed|null|string|string[]
     */
    public function prepareFilePath($path)
    {
        $path = str_replace(['\\'], DIRECTORY_SEPARATOR, $path);
        $path = preg_replace('|([/]+)|s', DIRECTORY_SEPARATOR, $path);
        $path = rtrim($path, DIRECTORY_SEPARATOR);
        return $path;
    }

    /**
     * @return bool
     * @throws FileSystemException
     */
    public function deleteFile()
    {
        $filePath = $this->directory->getAbsolutePath($this->getImportFilePath());
        $result = false;
        if ($this->getData('delete_file_after_import')
            && $this->directory->isFile($filePath)
        ) {
            $result = $this->directory->delete($filePath);
        }
        return $result;
    }

    /**
     * @param $fileName
     * @return bool
     * @throws FileSystemException
     */
    public function removeFile(string $fileName)
    {
        return $this->_getSourceClient()->delete($this->getFilePath($fileName));
    }

    /**
     * @param $fileSearchRegex
     * @return array
     */
    public function search(string $fileSearchRegex)
    {
        return $this->_getSourceClient()->search($fileSearchRegex);
    }

    /**
     * @inheridoc
     */
    public function isExists(string $path)
    {
        return $this->_getSourceClient()->isExist($path);
    }

    /**
     * @inheridoc
     */
    public function isAllowablePath(string $path)
    {
        if ($this->getScanDirectory()) {
            $basePath = $this->_getSourceClient()->getAbsolutePath();
            $absolutePath = $this->_getSourceClient()->getAbsolutePath($path);
            $realAbsolutePath = $this->fileDriver->getRealPath($absolutePath);
            $realPath = ltrim(mb_substr($realAbsolutePath, mb_strlen($basePath)), '/');
            $realPathPart = explode('/', $realPath);
            if (!$realPathPart || !isset($realPathPart[0])) {
                return false;
            }
            if (!in_array($realPathPart[0], $this->allowedDirsForScan())) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return string[]
     */
    public function allowedDirsForScan()
    {
        return ['pub', 'var'];
    }

    /**
     * @param string $source
     * @param string $target
     * @return bool
     * @throws FileSystemException
     */
    public function move(string $source, string $target)
    {
        if (!$this->_getSourceClient()->isExist($target)) {
            $this->_getSourceClient()->create(dirname($target));
        }
        return $this->_getSourceClient()->renameFile($source, $target);
    }

    /**
     * @param string $importFile
     * @return string
     */
    public function getFilePath(string $importFile)
    {
        return $this->_getSourceClient()->getAbsolutePath($importFile);
    }

    /**
     * @return bool
     */
    public function isAllowedDelete()
    {
        return true;
    }
}
