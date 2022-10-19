<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Api\Export\History;

use Firebear\ImportExport\Api\Export\History\CreateInterface;
use Firebear\ImportExport\Api\Export\History\GetListInterface;
use Firebear\ImportExport\Api\Export\History\CompressInterface;
use Firebear\ImportExport\Api\Export\History\SaveInterface;
use Firebear\ImportExport\Api\Data\ExportHistoryInterface;
use Firebear\ImportExport\Model\ResourceModel\Export\History as HistoryResource;
use Firebear\ImportExport\Model\Export\HistoryFactory;
use Firebear\ImportExport\Model\ExportJob\Processor;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Filesystem;
use Psr\Log\LoggerInterface;

/**
 * Create command (Service Provider Interface - SPI)
 *
 * @api
 */
class Create implements CreateInterface
{
    /**
     * @var HistoryFactory
     */
    private $historyFactory;

    /**
     * @var HistoryResource
     */
    private $resource;

    /**
     * @var Processor
     */
    private $processor;

    /**
     * @var TimezoneInterface
     */
    private $timeZone;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var GetListInterface
     */
    private $getListCommand;

    /**
     * @var SaveInterface
     */
    private $saveCommand;

    /**
     * @var CompressInterface
     */
    private $compressCommand;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var File
     */
    private $file;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Initialize command
     *
     * @param HistoryFactory $historyFactory
     * @param HistoryResource $resource
     * @param Processor $processor
     * @param TimezoneInterface $timezone
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param GetListInterface $getListCommand
     * @param CompressInterface $compressCommand
     * @param SaveInterface $saveCommand
     * @param Filesystem $filesystem
     * @param File $file
     */
    public function __construct(
        HistoryFactory $historyFactory,
        HistoryResource $resource,
        Processor $processor,
        TimezoneInterface $timezone,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        GetListInterface $getListCommand,
        CompressInterface $compressCommand,
        SaveInterface $saveCommand,
        Filesystem $filesystem,
        File $file
    ) {
        $this->historyFactory = $historyFactory;
        $this->resource = $resource;
        $this->processor = $processor;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->getListCommand = $getListCommand;
        $this->compressCommand = $compressCommand;
        $this->saveCommand = $saveCommand;
        $this->filesystem = $filesystem;
        $this->timeZone = $timezone;
        $this->file = $file;
    }

    /**
     * Execute command
     *
     * @param int $id
     * @param string $file
     * @param string $type
     * @return ExportHistoryInterface
     */
    public function execute($id, $file, $type)
    {
        $history = $this->historyFactory->create();
        $this->resource->load($history, $file, 'file');

        if (!$history->getId()) {
            /* move previous file */
            $this->move($id, $type);
            /* create new history */
            $history->setJobId($id);
            $history->setFile($file);
            $history->setType($type);
            $history->setStartedAt($this->getTimestamp());
        }
        return $history;
    }

    /**
     * @param LoggerInterface $logger
     * @return void
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * Move history files
     *
     * @param int $id
     * @param string $type
     * @return void
     */
    private function move($id, $type)
    {
        $this->searchCriteriaBuilder->addFilter('job_id', $id, 'in');
        $this->searchCriteriaBuilder->addFilter('is_moved', 0);

        $items = $this->getListCommand->execute(
            $this->searchCriteriaBuilder->create()
        )->getItems();

        if ('admin' !== $type) {
            $this->processor->inConsole = 1;
        }
        $data = $this->processor->prepareJob($id);
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::ROOT);
        foreach ($items as $item) {
            $path = $item->getTempFile();
            if (empty($path)) {
                continue;
            }
            $newPath = $this->getFilePath($path, $item->getFinishedAt());
            if ($directory->isFile($path) && $directory->renameFile($path, $newPath)) {
                if (!empty($data[Processor::BEHAVIOR_DATA]['archive_before_run']) &&
                    empty($data[Processor::BEHAVIOR_DATA]['archive_after_export'])
                ) {
                    $newPath = $this->compressCommand->execute($newPath);
                    $this->logger->info(
                        __(
                            'Previously exported file from %1 has been archived with the name %2',
                            $item->getFinishedAt(),
                            $newPath
                        )
                    );
                }
                $item->isMoved(1);
                $item->setTempFile($newPath);
                $this->saveCommand->execute($item);
            }
        }
    }

    /**
     * Return current date into timestamp
     *
     * @return string
     */
    private function getTimestamp()
    {
        return (string)$this->timeZone->date()->getTimestamp();
    }

    /**
     * Return new file path
     *
     * @param string $path
     * @param string $date
     * @return string
     */
    private function getFilePath($path, $date)
    {
        $filename = '';
        $info = $this->file->getPathInfo($path);
        $date = $this->timeZone->date($date)->format('Y-m-d-H:i:s');
        $filename = $info['dirname'] . '/' . $info['filename'] . '-' . $date;
        if (isset($info['extension'])) {
            $filename .= '.' . $info['extension'];
        }
        return $filename;
    }
}
