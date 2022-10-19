<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Job\Handler;

use Firebear\ImportExport\Helper\Data as Helper;
use Firebear\ImportExport\Logger\Logger;
use Firebear\ImportExport\Api\Data\ImportInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * @api
 */
class SummaryHandler implements HandlerInterface
{
    /**
     * @var ConsoleOutput
     */
    private $output;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @param Logger $logger
     * @param ConsoleOutput $output
     */
    public function __construct(
        Logger $logger,
        ConsoleOutput $output,
        Helper $helper
    ) {
        $this->logger = $logger;
        $this->output = $output;
        $this->helper = $helper;
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
        $this->logger->setFileName($file);
        $message = $status
            ? 'The import of the job "%1" with id "%2" was successful'
            : 'The import of the job "%1" with id "%2" was failure';

        $this->addLogComment(
            __($message, $job->getTitle(), $job->getId())
        );
        if ($this->helper->isEnableDbLogStorage()) {
            $history = $this->helper->loadHistoryByFileName($file);
            if (!empty($history)) {
                $this->helper->saveFullImportHistoryToDb($history);
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
