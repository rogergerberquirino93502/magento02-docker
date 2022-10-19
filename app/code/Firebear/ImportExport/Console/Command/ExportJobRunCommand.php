<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Console\Command;

use Magento\Backend\App\Area\FrontNameResolver;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command prints list of available currencies
 */
class ExportJobRunCommand extends ExportJobAbstractCommand
{
    const INPUT_KEY_LOG = 'log';
    const SHORTCUT_KEY_LOG = 'l';
    const INPUT_KEY_ADMIN = 'admin';
    const SHORTCUT_KEY_ADMIN = 'a';
    const INPUT_KEY_HISTORY = 'history';
    const SHORTCUT_KEY_HISTORY = 's';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('export:job:run')
            ->setDescription('Generate Firebear Export Jobs')
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
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $isAreaCode = 0;
        try {
            if ($this->state->getAreaCode()) {
                $isAreaCode = 1;
            }
        } catch (\Exception $e) {
            $isAreaCode = 0;
        }
        if (!$isAreaCode) {
            $this->state->setAreaCode(FrontNameResolver::AREA_CODE);
        }
        $timeStart = time();
        $requestedIds = $input->getArgument(self::JOB_ARGUMENT_NAME);
        $requestedIds = array_filter(array_map('trim', $requestedIds), 'strlen');
        $jobCollection = $this->factory->create()->getCollection();
        $jobCollection->addFieldToFilter('is_active', 1);

        if ($requestedIds) {
            $jobCollection->addFieldToFilter('entity_id', ['in' => $requestedIds]);
        }

        if ($jobCollection->getSize()) {
            foreach ($jobCollection as $job) {
                $id = (int)$job->getEntityId();
                $result = false;
                try {
                    $file = $this->helper->beforeRun($id);
                    $history = $this->helper->createExportHistory($id, $file, 'console');
                    $this->processor->debugMode = $this->debugMode = $this->helper->getDebugMode();
                    $this->processor->setLogger($this->helper->getLogger());
                    $this->processor->inConsole = 1;
                    $result = $this->processor->process($id, $history);
                    $timeFinish = time();
                    $totalTime = $timeFinish - $timeStart;
                    if ($result === true) {
                        $this->addLogComment(
                            'Job #' . $id . ' was generated successfully in ' . $totalTime . ' seconds',
                            $output,
                            'info'
                        );
                    } else {
                        $this->addLogComment(
                            is_array($result) ? $result[0] : $result,
                            $output,
                            'error'
                        );
                    }
                    $this->helper->saveFinishExHistory($history);
                } catch (\Exception $e) {
                    $this->addLogComment(
                        'Job #' . $id . ' can\'t be exported. Check if job exist',
                        $output,
                        'error'
                    );
                    $this->addLogComment(
                        $e->getMessage(),
                        $output,
                        'error'
                    );
                } finally {
                    $this->sender->sendEmail(
                        $job,
                        $file,
                        (int)$result
                    );
                }
            }
        } else {
            $this->addLogComment(
                'No jobs found',
                $output,
                'error'
            );
        }
    }

    /**
     * @param $jobId
     * @param $lastEntityId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function updateLastEntityId($jobId, $lastEntityId)
    {
        $exportJob = $this->repository->getById($jobId);
        $sourceData = $exportJob->getExportSource();
        $sourceData = array_merge(
            $sourceData,
            [
                'last_entity_id' => $lastEntityId
            ]
        );
        $exportJob->setExportSource($sourceData);
        $this->repository->save($exportJob);
    }
}
