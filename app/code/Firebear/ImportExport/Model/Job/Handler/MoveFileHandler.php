<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Job\Handler;

use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Firebear\ImportExport\Model\Source\Type\SearchSourceTypeInterface;
use Firebear\ImportExport\Api\Data\ImportInterface;
use Firebear\ImportExport\Model\Job\Processor;

/**
 * @api
 */
class MoveFileHandler implements HandlerInterface
{
    /**
     * @var Processor
     */
    private $processor;

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Processor $importProcessor
     * @param LoggerInterface $logger
     */
    public function __construct(
        Processor $processor,
        LoggerInterface $logger
    ) {
        $this->processor = $processor;
        $this->logger = $logger;
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
        if (!empty($data[SearchSourceTypeInterface::SCAN_DIR])) {
            $source = $this->processor->getImportModel()->getSource();
            if ($source instanceof SearchSourceTypeInterface) {
                $importFile = $this->processor->getImportFile();

                $dirName = $status ? SearchSourceTypeInterface::COM_DIR : SearchSourceTypeInterface::ERR_DIR;
                $path = $source->getConfigFilePath() . $source->getDirName($dirName);
                $newPath = $path . basename($importFile);

                try {
                    if ($source->move($importFile, $newPath)) {
                        $this->processor->addLogComment(
                            __('Moving file to %1 after import', $newPath),
                            $this->processor->getOutput(),
                            'info'
                        );
                    } else {
                        $this->processor->addLogComment(
                            __('Failed to move file to sub directory - %1"', $newPath),
                            $this->processor->getOutput(),
                            'error'
                        );
                    }
                } catch (LocalizedException $e) {
                    $this->processor->addLogComment(
                        $e->getMessage(),
                        $this->processor->getOutput(),
                        'error'
                    );
                } catch (\Exception $e) {
                    $this->logger->error($e->getMessage());
                    $this->processor->addLogComment(
                        __('Error occured detailed error in logger file'),
                        $this->processor->getOutput(),
                        'error'
                    );
                }
            }
        }
    }
}
