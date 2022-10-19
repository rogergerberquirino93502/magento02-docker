<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Api\Import;

use Firebear\ImportExport\Api\Import\SaveInterface;
use Firebear\ImportExport\Api\Data\ImportInterface;
use Firebear\ImportExport\Model\ResourceModel\Job as JobResource;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Save job data command (Service Provider Interface - SPI)
 *
 * @api
 */
class Save implements SaveInterface
{
    /**
     * @var JobResource
     */
    private $jobResource;

    /**
     * Initialize command
     *
     * @param JobResource $jobResource
     */
    public function __construct(
        JobResource $jobResource
    ) {
        $this->jobResource = $jobResource;
    }

    /**
     * Save job
     *
     * @param ImportInterface $job
     * @return ImportInterface
     * @throws CouldNotSaveException
     */
    public function execute(ImportInterface $job)
    {
        try {
            $this->jobResource->save($job);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the job: %1',
                $exception->getMessage()
            ));
        }
        return $job;
    }
}
