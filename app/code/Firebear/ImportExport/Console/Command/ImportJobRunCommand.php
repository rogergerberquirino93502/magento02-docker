<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Console\Command;

use Firebear\ImportExport\Logger\Logger;
use Firebear\ImportExport\Api\Import\RunByIdsInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Magento\Framework\Console\Cli;
use Magento\Framework\App\State;

/**
 * Import Run Command
 */
class ImportJobRunCommand extends ImportJobAbstractCommand
{
    /**
     * @var RunByIdsInterface
     */
    private $runByIds;

    /**
     * Constructor
     *
     * @param Logger $logger
     * @param State $state
     * @param RunByIdsInterface $runById
     */
    public function __construct(
        Logger $logger,
        State $state,
        RunByIdsInterface $runByIds
    ) {
        $this->runByIds = $runByIds;

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
        $this->setName('import:job:run')
            ->setDescription('Generate Firebear Import Jobs')
            ->setDefinition(
                [
                    new InputArgument(
                        self::JOB_ARGUMENT_NAME,
                        InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                        'Space-separated list of import job ids or omit to generate all jobs.'
                    )
                ]
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->runByIds->execute($this->getJobIds($input), 'console')) {
            return Cli::RETURN_SUCCESS;
        }
        $this->addLogComment('No jobs found', $output, 'error');

        return Cli::RETURN_FAILURE;
    }
}
