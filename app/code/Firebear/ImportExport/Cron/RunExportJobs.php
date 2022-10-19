<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Cron;

use Firebear\ImportExport\Model\ExportJob\Processor;
use Firebear\ImportExport\Model\Email\Sender;

/**
 * Class RunExportJobs
 *
 * @package Firebear\ImportExport\Cron
 */
class RunExportJobs
{
    /**
     * @var Processor
     */
    protected $processor;

    /**
     * @var \Firebear\ImportExport\Helper\Data
     */
    protected $helper;

    /**
     * Email sender
     *
     * @var Sender
     */
    protected $sender;

    /**
     * RunExportJobs constructor.
     *
     * @param Processor $exportProcessor
     * @param Sender $sender
     */
    public function __construct(
        Processor $exportProcessor,
        \Firebear\ImportExport\Helper\Data $helper,
        Sender $sender
    ) {
        $this->helper = $helper;
        $this->processor = $exportProcessor;
        $this->sender = $sender;
    }

    /**
     * @param $schedule
     *
     * @return bool
     */
    public function execute($schedule)
    {
        $jobCode = $schedule->getJobCode();
        preg_match('/_id_([0-9]+)/', $jobCode, $matches);
        if (isset($matches[1]) && (int)$matches[1] > 0) {
            $jobId = (int)$matches[1];
            $file = $this->helper->beforeRun($jobId);
            $history = $this->helper->createExportHistory($jobId, $file, 'cron');
            $this->processor->debugMode = $this->helper->getDebugMode();
            $this->processor->setLogger($this->helper->getLogger());
            $this->processor->inConsole = 1;
            $result = $this->processor->process($jobId, $history);
            $this->helper->saveFinishExHistory($history);

            $this->sender->sendEmail(
                $this->processor->getJob(),
                $file,
                (int)$result
            );
            return true;
        }

        return false;
    }
}
