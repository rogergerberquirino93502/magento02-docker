<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Api\Import;

use Firebear\ImportExport\Api\Import\GetByIdInterface;
use Firebear\ImportExport\Api\Data\ImportInterface;
use Firebear\ImportExport\Model\JobFactory;
use Firebear\ImportExport\Model\ResourceModel\Job as JobResource;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Get job by id command (Service Provider Interface - SPI)
 *
 * @api
 */
class GetById implements GetByIdInterface
{
    /**
     * @var JobResource
     */
    private $jobResource;

    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * Initialize command
     *
     * @param JobResource $jobResource
     * @param JobFactory $jobFactory
     */
    public function __construct(
        JobResource $jobResource,
        JobFactory $jobFactory
    ) {
        $this->jobResource = $jobResource;
        $this->jobFactory = $jobFactory;
    }

    /**
     * Get job by id
     *
     * @param int $jobId
     * @return ImportInterface
     * @throws NoSuchEntityException
     */
    public function execute($jobId)
    {
        /** @var ImportInterface $job */
        $job = $this->jobFactory->create();
        $this->jobResource->load($job, (string)$jobId);

        if (!$job->getId()) {
            throw new NoSuchEntityException(
                __('Job with id "%1" does not exist.', $jobId)
            );
        }
        return $job;
    }
}
