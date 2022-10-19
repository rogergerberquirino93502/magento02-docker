<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Job\Handler;

use Firebear\ImportExport\Api\Data\ImportInterface;
use Firebear\ImportExport\Model\Job\Processor;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use ZipArchive as Archive;
use Magento\Framework\Filesystem\DirectoryList;

/**
 * @api
 */
class CompressHandler implements HandlerInterface
{
    /**
     * @var Processor
     */
    private $processor;

    /**
     * @var Archive
     */
    private $archive;

    /**
     * @var TimezoneInterface
     */
    private $timeZone;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @param Processor $importProcessor
     * @param Archive $archive
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        Processor $processor,
        Archive $archive,
        TimezoneInterface $timezone,
        DirectoryList $directoryList
    ) {
        $this->processor = $processor;
        $this->archive = $archive;
        $this->timeZone = $timezone;
        $this->directoryList = $directoryList;
    }

    /**
     * Execute the handler
     *
     * @param ImportInterface $job
     * @param string $file
     * @param int $status
     * @return void
     */
    public function execute(ImportInterface $job, $file, $status)
    {
        $data = $job->getSourceData();
        $importSource = $data['import_source'] ?? '';
        if (!empty($data['archive_file_after_import'])) {
            $import = $this->processor->getImportModel();
            $platform = $import->getPlatform($data['platforms'] ?? null, $job->getEntity());

            $isGateway = $platform && $platform->isGateway();
            if (!$isGateway) {
                if ($import->getSource()->isRemote()) {
                    $path = $import->getSource()->getTempFilePath();
                    if (empty($path)) {
                        if ($data['scan_directory'] == 1) {
                            $path = $this->processor->getImportFile();
                        } else {
                            $path = $this->directoryList->getRoot() . '/' . $data['file_path'];
                        }
                    }
                    if ($this->compress($path, $importSource)) {
                        /* remove uploaded temp file */
                        $import->getSource()->resetSource(true);
                    }
                }
            }
        }
    }

    /**
     * Compress file
     *
     * @param string $path
     * @param string $importSource
     * @return bool
     */
    private function compress($path, $importSource)
    {
        $newPath = $this->getFilePath($importSource);
        $open = $this->archive->open($newPath, Archive::CREATE);
        if ($open == true) {
            $this->archive->addFile($path, basename($path));
            return $this->archive->close();
        }
        return false;
    }

    /**
     * Return new file path
     *
     * @param string $path
     * @return string
     */
    private function getFilePath($importSource)
    {
        $newFileDirectory = $this->directoryList->getRoot() . '/var/import/archives/' . $importSource . '/';
        if (!is_dir($newFileDirectory)) {
            mkdir($newFileDirectory, 0775, true);
        }
        $date = $this->timeZone->date()->format('Y-m-d-H:i:s');
        return $newFileDirectory . $date . '.zip';
    }
}
