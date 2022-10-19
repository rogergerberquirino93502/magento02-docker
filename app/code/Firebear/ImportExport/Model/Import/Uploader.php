<?php
declare(strict_types=1);
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import;

use function array_flip;
use function array_search;
use function class_exists;
use function explode;
use finfo;
use Firebear\ImportExport\Helper\MediaHelper;
use Firebear\ImportExport\Model\Filesystem\File\ReadFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\DriverPool;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Image\AdapterFactory;
use Magento\MediaStorage\Helper\File\Storage;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\MediaStorage\Model\File\Validator\NotProtectedExtension;
use function pathinfo;
use function strpos;
use Symfony\Component\Console\Output\ConsoleOutput;
use Zend_Uri;

/**
 * Class Uploader
 * @api
 * @since 100.0.2
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @package Firebear\ImportExport\Model\Import
 */
class Uploader extends \Magento\CatalogImportExport\Model\Import\Uploader
{
    /**
     * Default User Agent chain to prevent 403 forbidden issue
     */
    const DEFAULT_HTTP_USER_AGENT = "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)";

    protected $httpScheme = 'http://';
    /**
     * @var MediaHelper
     */
    protected $mediaHelper;

    /**
     * @var Curl
     */
    protected $curl;

    protected $entity;

    /**
     * @var ConsoleOutput
     */
    protected $output;

    /**
     * Uploader constructor.
     *
     * @param Database $coreFileStorageDb
     * @param Storage $coreFileStorage
     * @param AdapterFactory $imageFactory
     * @param NotProtectedExtension $validator
     * @param Filesystem $filesystem
     * @param Filesystem\File\ReadFactory $readFactory
     * @param ReadFactory $fireReadFactory
     * @param MediaHelper $mediaHelper
     * @param Curl $curl
     * @param null $filePath
     *
     * @throws FileSystemException
     * @throws LocalizedException
     */
    public function __construct(
        Database $coreFileStorageDb,
        Storage $coreFileStorage,
        AdapterFactory $imageFactory,
        NotProtectedExtension $validator,
        Filesystem $filesystem,
        Filesystem\File\ReadFactory $readFactory,
        ReadFactory $fireReadFactory,
        MediaHelper $mediaHelper,
        Curl $curl,
        $filePath = null
    ) {
        parent::__construct(
            $coreFileStorageDb,
            $coreFileStorage,
            $imageFactory,
            $validator,
            $filesystem,
            $readFactory,
            $filePath
        );

        $this->_readFactory = $fireReadFactory;
        $this->mediaHelper = $mediaHelper;
        $this->curl = $curl;
    }

    /**
     * @param $entity
     * @return $this
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
        return $this;
    }

    /**
     * Set TMP file path prefix
     *
     * @param string $path
     * @return bool
     * @throws FileSystemException
     */
    public function setTmpDir($path)
    {
        if (is_string($path)) {
            if (!$this->_directory->isExist($path)) {
                $this->_directory->create($path);
            }
            return parent::setTmpDir($path);
        }
        return false;
    }

    /**
     * @param string $fileName
     * @param bool $renameFileOff
     * @param array $existingUpload
     *
     * @return array
     * @throws FileSystemException
     * @throws LocalizedException
     */
    public function move($fileName, $renameFileOff = false, $existingUpload = [])
    {
        $fileName = trim($fileName);
        if ($this->checkValidUrl($fileName)) {
            if (strpos($fileName, MediaHelper::YOUTUBE) !== false) {
                $fileName = $this->mediaHelper->getYoutubeVideoImage($fileName);
            } elseif (strpos($fileName, MediaHelper::VIMEO) !== false) {
                $fileName = $this->mediaHelper->getVimeoVideoImage($fileName);
            }
        } elseif (strpos($fileName, MediaHelper::YOUTUBE) !== false
            || strpos($fileName, MediaHelper::VIMEO) !== false
        ) {
            $this->entity->addLogWriteln(
                __('Error with downloading image for %1', $fileName),
                $this->getOutput(),
                'error'
            );
            return [];
        }
        $file_info = '';
        $mime_type = $this->getImageMimeType();
        if ($renameFileOff) {
            $this->setAllowRenameFiles(false);
        }
        if (preg_match('/\bhttps?:\/\//i', $fileName, $matches)) {
            if (class_exists(finfo::class)) {
                $file_info = new finfo(FILEINFO_MIME_TYPE);
            }
            $url = str_replace($matches[0], '', $fileName);
            $httpDriver = ($matches[0] === $this->httpScheme) ? DriverPool::HTTP . '://' : DriverPool::HTTPS . '://';
            $urlProp = $this->parseUrl($httpDriver . $url);
            $hostname = $urlProp['host'];
            $path = $urlProp['path'];
            $name = hash('sha256', $fileName);

            $existingUpload = [];
            $existName = $name;
            if (!array_key_exists(pathinfo($existName, PATHINFO_EXTENSION), $this->_allowedMimeTypes)) {
                $existName .= $this->getImageMimeType($mime_type);
            }

            $existFilePath = $this->_directory->getRelativePath($this->getTmpDir() . '/' . $existName);
            if ($this->_directory->isExist($existFilePath)) {
                $destDir = $this->_directory->getAbsolutePath($this->getDestDir());
                $destFile = static::_addDirSeparator(static::getDispersionPath($existName)) . $existName;
                if ($this->_directory->isExist($destDir . $destFile)) {
                    return ['file' => $destFile];
                }
                $this->_setUploadFile($existFilePath);
                return $this->save($destDir);
            }

            if (!$this->isImageQueryString($fileName)) {
                $this->getCurl()
                    ->setOptions(
                        [
                            CURLOPT_RETURNTRANSFER => 1,
                            CURLOPT_BINARYTRANSFER => 1,
                            CURLOPT_FOLLOWLOCATION => 1,
                            CURLOPT_UNRESTRICTED_AUTH => 1,
                        ]
                    );
                $url = $httpDriver . $hostname . $path;
                $newPath = [];
                if (!Zend_Uri::check($url)) {
                    foreach (explode('/', $path) as $p) {
                        $newPath[] = rawurlencode($p);
                    }
                    $path = implode('/', $newPath);
                    $path = str_ireplace(['%C4_', '%C5_'], ['%C4%9F', '%C5%9E'], $path);
                    $url = $this->httpScheme . $hostname . $path;
                }

                if (isset($urlProp['user'], $urlProp['pass'])) {
                    $this->getCurl()
                        ->setOption(
                            CURLOPT_USERPWD,
                            $urlProp['user'] . ':' . $urlProp['pass']
                        );
                }

                $this->getCurl()->get($url);
                $data = $this->getCurl()->getBody();

                if (!getimagesizefromstring($data)) {
                    $this->entity->addLogWriteln(
                        __('Image not found string illegal %1', $url),
                        $this->getOutput(),
                        'error'
                    );
                }
                if ($file_info instanceof finfo) {
                    $mime_type = $file_info->buffer($data);
                }
            } else {
                $options = ['http' => ['user_agent' => self::DEFAULT_HTTP_USER_AGENT]];
                $context = stream_context_create($options);
                $data = $this->_directory->getDriver()->fileGetContents($fileName, null, $context);
                if ($file_info instanceof finfo) {
                    $mime_type = $file_info->buffer($data);
                }
            }

            $tmpDir = $this->_directory->getAbsolutePath($this->getTmpDir());
            $tmpPath = static::_addDirSeparator($tmpDir) . $name;
            $this->_directory->writeFile($tmpPath, $data);

            if (!array_key_exists(pathinfo($name, PATHINFO_EXTENSION), $this->_allowedMimeTypes)) {
                $name .= $this->getImageMimeType($name);
            }

            $fileName = static::_addDirSeparator(static::getDispersionPath($name)) . $name;
            $this->_directory->renameFile($tmpPath, $tmpDir . $fileName);
        }

        $filePath = $this->_directory->getRelativePath($this->getTmpDir() . DIRECTORY_SEPARATOR . $fileName);
        $this->_setUploadFile($filePath);

        if (!isset($name)) {
            $name = $fileName;
        }
        $key = array_search($this->getUploadedFileName(), $existingUpload);
        if ($key !== false) {
            $exp = explode('.', $name);
            $newFileName = $exp[0] . '_' . $key . '.' . $exp[1];
        } else {
            $newFileName = $name;
        }

        $destDir = $this->_directory->getAbsolutePath($this->getDestDir());
        $result = $this->save($destDir, $newFileName);

        unset($result['path']);

        $result['name'] = self::getCorrectFileName($result['name']);
        return $result;
    }

    /**
     * @param $url
     *
     * @return bool
     */
    public function checkValidUrl($url)
    {
        return $this->mediaHelper->checkValidUrl($url);
    }

    /**
     * @param string $mime_type
     *
     * @return string
     */
    private function getImageMimeType($mime_type = 'image/jpeg')
    {
        $fileExtension = array_flip($this->_allowedMimeTypes)[$mime_type] ?? 'jpeg';
        return '.' . $fileExtension;
    }

    /**
     * @param $path
     * @return array|false|int|string|null
     */
    protected function parseUrl($path)
    {
        return parse_url($path);
    }

    /**
     * @param $fileName
     *
     * @return bool
     */
    private function isImageQueryString($fileName)
    {
        return strpos($fileName, '?') !== false;
    }

    /**
     * @return Curl
     */
    public function getCurl()
    {
        return $this->curl;
    }

    /**
     * @return ConsoleOutput
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param ConsoleOutput|null $output
     */
    public function setOutput(ConsoleOutput $output = null)
    {
        $this->output = $output;
    }

    /**
     * Create folder
     *
     * @param string $directory
     *
     * @return \Magento\Framework\File\Uploader
     * @throws LocalizedException
     */
    public function createDirectory($directory)
    {
        if (!$directory || !$this->_allowCreateFolders) {
            return $this;
        }

        $directory = (substr($directory, -1) == '/') ? substr($directory, 0, -1) : $directory;

        if (!(is_dir($directory) || mkdir($directory, 0777, true))) {
            throw new LocalizedException(__(
                'Unable to create directory \'%1\'',
                $directory
            ));
        }
        return $this;
    }

    /**
     * @param $directory
     *
     * @return bool
     */
    public function isDirectoryWritable($directory)
    {
        return $this->_directory->isWritable($directory);
    }

    /**
     * @return array
     */
    public function getAllowedFileExtension()
    {
        return array_keys($this->_allowedMimeTypes);
    }

    /**
     * Get dispersion path
     *
     * @param string $fileName
     * @return string
     */
    public static function getDispersionPath($fileName)
    {
        $char = 0;
        $dispersionPath = '';
        while ($char < 2 && $char < strlen($fileName)) {
            if (empty($dispersionPath)) {
                $dispersionPath = '/' . ('.' == $fileName[$char] ? '_' : $fileName[$char]);
            } else {
                $dispersionPath = self::_addDirSeparator($dispersionPath)
                    . ('.' == $fileName[$char] ? '_' : $fileName[$char]);
            }
            $char++;
        }
        return $dispersionPath;
    }
}
