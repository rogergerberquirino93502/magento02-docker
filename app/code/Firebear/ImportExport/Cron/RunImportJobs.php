<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Cron;

use Firebear\ImportExport\Api\Import\RunByStrategyInterface;
use Firebear\ImportExport\Api\Import\GetByIdInterface;

/**
 * Import Cron
 */
class RunImportJobs
{
    /**
     * @var RunByStrategyInterface
     */
    private $runByStrategy;

    /**
     * @var GetByIdInterface
     */
    private $getById;

    /**
     * Initialize
     *
     * @param RunByStrategyInterface $runByStrategy
     * @param GetByIdInterface $getById
     */
    public function __construct(
        RunByStrategyInterface $runByStrategy,
        GetByIdInterface $getById
    ) {
        $this->runByStrategy = $runByStrategy;
        $this->getById = $getById;
    }

    /**
     * @param $schedule
     *
     * @return void
     */
    public function execute($schedule)
    {
        $jobCode = $schedule->getJobCode();
        preg_match('/_id_([0-9]+)/', $jobCode, $matches);
        $jobId = (int)($matches[1] ?? 0);
        if (0 < $jobId) {
            $job = $this->getById->execute($jobId);
            $this->runByStrategy->execute($job, 'cron');
        }
    }
}
