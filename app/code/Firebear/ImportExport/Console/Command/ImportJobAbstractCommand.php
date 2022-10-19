<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Console\Command;

use Magento\Framework\App\State;
use Magento\Backend\App\Area\FrontNameResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Firebear\ImportExport\Logger\Logger;

/**
 * Command prints list of available currencies
 */
class ImportJobAbstractCommand extends Command
{
    const JOB_ARGUMENT_NAME = 'job';

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var bool
     */
    protected $debugMode;

    /**
     * @var State
     */
    protected $state;

    /**
     * ImportJobAbstractCommand constructor.
     *
     * @param Logger $logger
     * @param State $state
     */
    public function __construct(
        Logger $logger,
        State $state
    ) {
        $this->state = $state;
        $this->logger = $logger;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->state->setAreaCode(FrontNameResolver::AREA_CODE);
        } catch (\Exception $e) {
            $this->debugMode = false;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getJobIds(InputInterface $input)
    {
        $jobIds = $input->getArgument(
            self::JOB_ARGUMENT_NAME
        );
        return (array)array_filter(array_map('trim', $jobIds), 'strlen');
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
                    $debugData = '<error>' . $debugData . '</error>';
                    break;
                case 'info':
                    $debugData = '<info>' . $debugData . '</info>';
                    break;
                default:
                    $debugData = '<comment>' . $debugData . '</comment>';
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
