<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Console\Command;

use Firebear\ImportExport\Logger\Logger;
use Firebear\ImportExport\Api\Data\ImportInterface;
use Firebear\ImportExport\Api\Import\MassUpdateInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Magento\Framework\Console\Cli;
use Magento\Framework\App\State;

/**
 * Command prints list of available currencies
 */
class ImportJobDisableCommand extends ImportJobAbstractCommand
{
    /**
     * @var MassUpdateInterface
     */
    private $massUpdate;

    /**
     * Constructor
     *
     * @param Logger $logger
     * @param State $state
     * @param MassUpdateInterface $massUpdate
     */
    public function __construct(
        Logger $logger,
        State $state,
        MassUpdateInterface $massUpdate
    ) {
        $this->massUpdate = $massUpdate;

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
        $this->setName('import:job:disable')
            ->setDescription('Disable Firebear Import Jobs')
            ->setDefinition(
                [
                    new InputArgument(
                        self::JOB_ARGUMENT_NAME,
                        InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                        'Space-separated list of job ids or omit to disable all jobs.'
                    )
                ]
            );

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $totalRecords = $this->massUpdate->execute(
            $this->getJobIds($input),
            ImportInterface::IS_ACTIVE,
            ImportInterface::STATUS_DISABLED
        );

        if ($totalRecords) {
            $message = __('A total of %1 job(s) have been disabled.', $totalRecords);
            $this->addLogComment($message, $output, 'info');
            return Cli::RETURN_SUCCESS;
        }
        $this->addLogComment('No jobs found', $output, 'error');

        return Cli::RETURN_FAILURE;
    }
}
