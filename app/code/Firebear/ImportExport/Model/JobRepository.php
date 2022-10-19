<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model;

use Firebear\ImportExport\Api\Data\ImportInterface;
use Firebear\ImportExport\Api\JobRepositoryInterface;
use Firebear\ImportExport\Api\Import\GetByIdInterface;
use Firebear\ImportExport\Api\Import\GetListInterface;
use Firebear\ImportExport\Api\Import\SaveInterface;
use Firebear\ImportExport\Api\Import\DeleteInterface;
use Firebear\ImportExport\Api\Import\DeleteByIdInterface;
use Firebear\ImportExport\Api\Import\SearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class JobRepository
 *
 * @package Firebear\ImportExport\Model
 */
class JobRepository implements JobRepositoryInterface
{
    /**
     * @var GetByIdInterface
     */
    private $commandGetById;

    /**
     * @var GetListInterface
     */
    private $commandGetList;

    /**
     * @var SaveInterface
     */
    private $commandSave;

    /**
     * @var DeleteInterface
     */
    private $commandDelete;

    /**
     * @var DeleteByIdInterface
     */
    private $commandDeleteById;

    /**
     * Initialize repository
     *
     * @param GetByIdInterface $commandGetById
     * @param GetListInterface $commandGetList
     * @param SaveInterface $commandSave
     * @param DeleteInterface $commandDelete
     * @param DeleteByIdInterface $commandDeleteById
     */
    public function __construct(
        GetByIdInterface $commandGetById,
        GetListInterface $commandGetList,
        SaveInterface $commandSave,
        DeleteInterface $commandDelete,
        DeleteByIdInterface $commandDeleteById
    ) {
        $this->commandGetById = $commandGetById;
        $this->commandGetList = $commandGetList;
        $this->commandSave = $commandSave;
        $this->commandDelete = $commandDelete;
        $this->commandDeleteById = $commandDeleteById;
    }

    /**
     * Save job
     *
     * @param ImportInterface $job
     * @return ImportInterface
     * @throws CouldNotSaveException
     */
    public function save(ImportInterface $job)
    {
        return $this->commandSave->execute($job);
    }

    /**
     * Get job by id
     *
     * @param int $jobId
     * @return ImportInterface
     * @throws NoSuchEntityException
     */
    public function getById($jobId)
    {
        return $this->commandGetById->execute($jobId);
    }

    /**
     * Delete job
     *
     * @param ImportInterface $job
     * @return bool true on success
     * @throws CouldNotDeleteException
     */
    public function delete(ImportInterface $job)
    {
        return $this->commandDelete->execute($job);
    }

    /**
     * Delete job by id
     *
     * @param int $jobId
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById($jobId)
    {
        return $this->commandDeleteById->execute($jobId);
    }

    /**
     * Get job list
     *
     * @param SearchCriteriaInterface|null $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria = null)
    {
        return $this->commandGetList->execute($searchCriteria);
    }
}
