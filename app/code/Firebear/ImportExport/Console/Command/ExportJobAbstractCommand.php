<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Console\Command;

use Firebear\ImportExport\Model\Email\Sender;
use Firebear\ImportExport\Model\ExportJob\Processor;
use Firebear\ImportExport\Model\ExportJob as Job;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command prints list of available currencies
 */
class ExportJobAbstractCommand extends Command
{
    const JOB_ARGUMENT_NAME = 'export_job';

    /**
     * @var Job
     */
    protected $job;

    /**
     * @var Processor
     */
    protected $processor;

    /**
     * @var \Firebear\ImportExport\Api\ExportJobRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Firebear\ImportExport\Model\ExportJobFactory
     */
    protected $factory;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $state;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Firebear\ImportExport\Helper\Data
     */
    protected $helper;

    protected $debugMode;

    /**
     * Email sender
     *
     * @var Sender
     */
    protected $sender;

    /**
     * ExportJobAbstractCommand constructor.
     * @param Job $job
     * @param Processor $importProcessor
     * @param \Firebear\ImportExport\Api\ExportJobRepositoryInterface $repository
     * @param \Firebear\ImportExport\Model\ExportJobFactory $factory
     * @param \Magento\Framework\App\State $state
     * @param \Firebear\ImportExport\Logger\Logger $logger
     * @param \Firebear\ImportExport\Helper\Data $helper
     * @param Sender $sender
     */
    public function __construct(
        Job $job,
        Processor $importProcessor,
        \Firebear\ImportExport\Api\ExportJobRepositoryInterface $repository,
        \Firebear\ImportExport\Model\ExportJobFactory $factory,
        \Magento\Framework\App\State $state,
        \Firebear\ImportExport\Logger\Logger $logger,
        \Firebear\ImportExport\Helper\Data $helper,
        Sender $sender
    ) {
        parent::__construct();
        $this->job = $job;
        $this->processor = $importProcessor;
        $this->repository = $repository;
        $this->factory = $factory;
        $this->state = $state;
        $this->logger = $logger;
        $this->helper = $helper;
        $this->sender = $sender;
    }

    /**
     * @param $debugData
     * @param OutputInterface|null $output
     * @param null $type
     * @return $this
     */
    public function addLogComment($debugData, OutputInterface $output = null, $type = null)
    {

        if ($this->debugMode) {
            $this->logger->debug($debugData);
        }

        if ($output) {
            switch ($type) {
                case 'error':
                    $debugData = '<error>' . $debugData .'</error>';
                    break;
                case 'info':
                    $debugData = '<info>' . $debugData .'</info>';
                    break;
                default:
                    break;
            }

            $output->writeln($debugData);
        }

        return $this;
    }

    /**
     * @param $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }
}
