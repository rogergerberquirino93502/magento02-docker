<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration\Field\Job\Youtube;

use Magento\Catalog\Model\Product\Media\Config;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\File\Uploader;
use Magento\Framework\Filesystem;
use Magento\Framework\HTTP\Adapter\Curl;
use Firebear\ImportExport\Model\Migration\Field\JobInterface;

/**
 * @inheritdoc
 */
class CreateThumbnail implements JobInterface
{
    /**
     * @var Config
     */
    protected $mediaConfig;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @param Config $mediaConfig
     * @param Curl $curl
     * @param Filesystem $filesystem
     */
    public function __construct(
        Config $mediaConfig,
        Curl $curl,
        Filesystem $filesystem
    ) {
        $this->mediaConfig = $mediaConfig;
        $this->curl = $curl;
        $this->filesystem = $filesystem;
    }

    /**
     * @inheritdoc
     */
    public function job(
        $sourceField,
        $sourceValue,
        $destinationFiled,
        $destinationValue,
        $sourceDataRow
    ) {
        $mediaPath = $this->mediaConfig->getBaseMediaPath();

        $localFileName = "youtube_thumbnail_{$sourceValue}.jpg";
        $localFileName = Uploader::getDispersionPath($localFileName) . DIRECTORY_SEPARATOR . $localFileName;
        $localFilePath = $mediaPath . $localFileName;

        $dir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);

        if (!$dir->isExist($localFilePath)) {
            $contents = $this->getRemoteImage($sourceValue);
            $dir->writeFile($localFilePath, $contents);
        }

        return $localFileName;
    }

    /**
     * Get youtube thumbnail url
     *
     * @param string $code
     *
     * @return string
     */
    protected function getThumbnailUrl($code)
    {
        return "https://img.youtube.com/vi/{$code}/hqdefault.jpg";
    }

    /**
     * Get remote image contents
     *
     * @param string $code
     *
     * @return string
     */
    protected function getRemoteImage($code)
    {
        $this->curl->setConfig(['header' => false]);
        $this->curl->write('GET', $this->getThumbnailUrl($code));
        $image = $this->curl->read();

        return $image;
    }
}
