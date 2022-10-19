<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Api\Export\History;

use Firebear\ImportExport\Api\Export\History\CompressInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use ZipArchive as Archive;

/**
 * Compress command (Service Provider Interface - SPI)
 *
 * @api
 */
class Compress implements CompressInterface
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Archive
     */
    private $archive;

    /**
     * Initialize command
     *
     * @param Filesystem $filesystem
     * @param Archive $archive
     */
    public function __construct(
        Filesystem $filesystem,
        Archive $archive
    ) {
        $this->filesystem = $filesystem;
        $this->archive = $archive;
    }

    /**
     * Execute command
     *
     * @param string $path
     * @return string
     */
    public function execute($path)
    {
        $newPath = $path . '.zip';
        $directory = $this->filesystem->getDirectoryWrite(
            DirectoryList::ROOT
        );

        $source = $directory->getAbsolutePath($path);
        $destination = $directory->getAbsolutePath($newPath);

        $this->archive->open($destination, \ZipArchive::CREATE);
        $this->archive->addFile($source, basename($path));
        $this->archive->close();

        $directory->delete($path);

        return $newPath;
    }
}
