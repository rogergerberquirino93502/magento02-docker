<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Api\Import;

use Firebear\ImportExport\Api\Import\DeleteInterface;
use Firebear\ImportExport\Api\Data\ImportInterface;
use Firebear\ImportExport\Model\ResourceModel\Job as JobResource;
use Magento\Framework\Exception\CouldNotDeleteException;

/**
 * Delete job command (Service Provider Interface - SPI)
 *
 * @api
 */
class Delete implements DeleteInterface
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
     * Delete job
     *
     * @param ImportInterface $job
     * @return ImportInterface
     * @throws CouldNotDeleteException
     */
    public function execute(ImportInterface $job)
    {
        try {
            $this->jobResource->delete($job);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the job: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }
}
