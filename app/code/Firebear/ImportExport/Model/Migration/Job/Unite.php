<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration\Job;

use Firebear\ImportExport\Model\Migration\AdditionalOptions;
use Symfony\Component\Console\Output\OutputInterface;
use Firebear\ImportExport\Model\Migration\JobInterface;

/**
 * @inheritdoc
 */
class Unite implements JobInterface
{
    /**
     * @var JobInterface[]
     */
    protected $jobs = [];

    /**
     * @param JobInterface[] $jobs
     */
    public function __construct(array $jobs)
    {
        $this->jobs = $jobs;
    }

    /**
     * @inheritdoc
     */
    public function job($output, $additionalOptions = null)
    {
        foreach ($this->jobs as $job) {
            $job->job($output, $additionalOptions);
        }
    }
}
