<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Api\Import;

use Firebear\ImportExport\Api\Data\ImportInterface;
use Firebear\ImportExport\Api\Import\DeleteByIdInterface;
use Firebear\ImportExport\Api\Import\DeleteInterface;
use Firebear\ImportExport\Api\Import\GetByIdInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Delete job by id command (Service Provider Interface - SPI)
 *
 * @api
 */
class DeleteById implements DeleteByIdInterface
{
    /**
     * @var GetByIdInterface
     */
    private $commandGetById;

    /**
     * @var DeleteInterface
     */
    private $commandDelete;

    /**
     * Initialize command
     *
     * @param GetByIdInterface $commandGetById
     * @param DeleteInterface $commandDelete
     */
    public function __construct(
        GetByIdInterface $commandGetById,
        DeleteInterface $commandDelete
    ) {
        $this->commandGetById = $commandGetById;
        $this->commandDelete = $commandDelete;
    }

    /**
     * Delete job by id
     *
     * @param int $jobId
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function execute($jobId)
    {
        /** @var ImportInterface $job */
        $job = $this->commandGetById->execute($jobId);
        return $this->commandDelete->execute($job);
    }
}
