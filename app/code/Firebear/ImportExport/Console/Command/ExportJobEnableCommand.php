<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Command prints list of available currencies
 */
class ExportJobEnableCommand extends ExportJobAbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('export:job:enable')
            ->setDescription('Enable Firebear Export Jobs')
            ->setDefinition(
                [
                    new InputArgument(
                        self::JOB_ARGUMENT_NAME,
                        InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                        'Space-separated list of job ids or omit to enable all jobs.'
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
        $requestedIds = $input->getArgument(self::JOB_ARGUMENT_NAME);
        $requestedIds = array_filter(array_map('trim', $requestedIds), 'strlen');
        $jobCollection = $this->job->getCollection();

        if ($requestedIds) {
            $jobCollection->addFieldToFilter('entity_id', ['in' => $requestedIds]);
        }

        if ($jobCollection->getSize()) {
            foreach ($jobCollection as $job) {
                $job->setIsActive(1);
                $this->repository->save($job);
                $this->addLogComment('Job #' . $job->getEntityId() . ' was enabled.', $output, 'info');
            }
        } else {
            $this->addLogComment('No jobs found', $output, 'error');
        }
    }
}
