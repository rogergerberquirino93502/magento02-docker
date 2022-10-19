<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Api;

use Firebear\ImportExport\Api\Data\ImportInterface;
use Firebear\ImportExport\Api\Import\SearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interface JobRepositoryInterface
 *
 * @package Firebear\ImportExport\Api
 */
interface JobRepositoryInterface
{
    /**
     * Save job
     *
     * @param ImportInterface $job
     * @return ImportInterface
     * @throws CouldNotSaveException
     */
    public function save(ImportInterface $job);

    /**
     * Get job by id
     *
     * @param int $jobId
     * @return ImportInterface
     * @throws NoSuchEntityException
     */
    public function getById($jobId);

    /**
     * Delete job
     *
     * @param ImportInterface $job
     * @return bool true on success
     * @throws CouldNotDeleteException
     */
    public function delete(ImportInterface $job);

    /**
     * Delete job by id
     *
     * @param int $jobId
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById($jobId);

    /**
     * Get job list
     *
     * @param SearchCriteriaInterface|null $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria = null);
}
