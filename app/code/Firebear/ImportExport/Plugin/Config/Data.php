<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

/**
 * Prepare cron jobs data
 */

namespace Firebear\ImportExport\Plugin\Config;

use Firebear\ImportExport\Cron\RunExportJobs;
use Firebear\ImportExport\Cron\RunImportJobs;
use Firebear\ImportExport\Model\ExportJob;
use Firebear\ImportExport\Model\ExportJobFactory;
use Firebear\ImportExport\Model\JobFactory;
use Psr\Log\LoggerInterface;

/**
 * Class Data
 * @package Firebear\ImportExport\Plugin\Config
 */
class Data
{
    /**
     * Import Job factory
     *
     * @var JobFactory
     */
    protected $jobFactory;

    /**
     * @var ExportJobFactory
     */
    protected $exportJobFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $jobCodePattern = 'importexport_jobs_run_id_%u';

    protected $jobCodeExportPattern = 'importexport_export_jobs_run_id_%u';

    /**
     * Data constructor.
     *
     * @param ExportJobFactory $exportJobFactory
     * @param JobFactory $jobFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ExportJobFactory $exportJobFactory,
        JobFactory $jobFactory,
        LoggerInterface $logger
    ) {
        $this->exportJobFactory = $exportJobFactory;
        $this->jobFactory = $jobFactory;
        $this->logger = $logger;
    }

    /**
     * Implement cron jobs created via admin panel into system cron jobs generated from xml files
     *
     * @param \Magento\Cron\Model\Config\Data $subject
     * @param                                 $result
     *
     * @return mixed
     */
    public function afterGetJobs(\Magento\Cron\Model\Config\Data $subject, $result)
    {
        $result = $this->scopeCrons($result, $this->jobFactory, RunImportJobs::class, $this->jobCodePattern);
        $result = $this->scopeCrons(
            $result,
            $this->exportJobFactory,
            RunExportJobs::class,
            $this->jobCodeExportPattern
        );
        return $result;
    }

    /**
     * @param $result
     * @param $factory
     * @param $instance
     * @param $pattern
     *
     * @return mixed
     */
    protected function scopeCrons($result, $factory, $instance, $pattern)
    {
        $jobCollection = $factory->create()->getCollection();
        $jobCollection->addFieldToFilter('is_active', 1);
        $jobCollection->addFieldToFilter('cron', ['neq' => '']);
        $jobCollection->load();
        foreach ($jobCollection as $job) {
            $jobName = sprintf($pattern, $job->getId());
            $jobSourceData = $job->getData('source_data');
            if ($job instanceof ExportJob) {
                $jobSourceData = $job->getData('export_source');
            }
            $cronGroupName = $jobSourceData['cron_groups'] ?? 'firebear_importexport';
            $result[$cronGroupName][$jobName] = [
                'name' => $jobName,
                'instance' => $instance,
                'method' => 'execute',
                'schedule' => $job->getCron()
            ];
        }

        return $result;
    }
}
