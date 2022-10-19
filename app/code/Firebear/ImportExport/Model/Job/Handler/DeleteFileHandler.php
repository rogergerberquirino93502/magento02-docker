<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Job\Handler;

use Firebear\ImportExport\Logger\Logger;
use Firebear\ImportExport\Api\Data\ImportInterface;
use Firebear\ImportExport\Model\Job\Processor;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * @api
 */
class DeleteFileHandler implements HandlerInterface
{
    /**
     * @var Processor
     */
    private $processor;

    /**
     * @var ConsoleOutput
     */
    private $output;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Processor $importProcessor
     * @param ConsoleOutput $output
     * @param Logger $logger
     */
    public function __construct(
        Processor $processor,
        ConsoleOutput $output,
        Logger $logger
    ) {
        $this->processor = $processor;
        $this->logger = $logger;
        $this->output = $output;
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
        if (!empty($data['delete_file_after_import'])) {
            $import = $this->processor->getImportModel();
            $platform = $import->getPlatform($data['platforms'] ?? null, $job->getEntity());

            $isGateway = $platform && $platform->isGateway();
            if (!$isGateway && $import->getSource()->isAllowedDelete()) {
                if ($import->getSource()->deleteFile()) {
                    $this->addLogComment(
                        __('The Import File Deleted Successfully')
                    );
                } else {
                    $this->addLogComment(
                        __('There was an error in removal of file or Already Removed')
                    );
                }
            }
        }
    }

    /**
     * Add message to log
     *
     * @param string $message
     * @return void
     */
    private function addLogComment($message)
    {
        $this->logger->info($message);
        if ($this->output) {
            $this->output->writeln($message);
        }
    }
}
