<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Console\Command;

use Firebear\ImportExport\Api\Import\RunChainInterface;
use Firebear\ImportExport\Logger\Logger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\Console\Cli;
use Magento\Framework\App\State;

/**
 * Import Run Chain Command
 */
class ImportJobRunChainCommand extends ImportJobAbstractCommand
{
    /**
     * @var RunChainInterface
     */
    private $runChain;

    /**
     * Constructor
     *
     * @param Logger $logger
     * @param State $state
     * @param RunChainInterface $runById
     */
    public function __construct(
        Logger $logger,
        State $state,
        RunChainInterface $runChain
    ) {
        $this->runChain = $runChain;

        parent::__construct(
            $logger,
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('import:job:run-chain')
            ->setDescription('Generate Firebear Import Jobs Chain');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->runChain->execute('console')) {
            return Cli::RETURN_SUCCESS;
        }
        $this->addLogComment('No jobs found', $output, 'error');

        return Cli::RETURN_FAILURE;
    }
}
