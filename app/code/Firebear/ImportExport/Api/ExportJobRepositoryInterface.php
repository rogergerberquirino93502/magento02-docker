<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Api;

/**
 * Interface ExportJobsRepositoryInterface
 *
 * @package Firebear\ImportExport\Api
 */
interface ExportJobRepositoryInterface
{
    /**
     * Save job.
     *
     * @param \Firebear\ImportExport\Api\Data\ExportInterface $job
     * @return \Firebear\ImportExport\Api\Data\ExportInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(Data\ExportInterface $job);

    /**
     * Retrieve job.
     *
     * @param int $jobId
     * @return \Firebear\ImportExport\Api\Data\ExportInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($jobId);

    /**
     * Delete job.
     *
     * @param \Firebear\ImportExport\Api\Data\ExportInterface $job
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(Data\ExportInterface $job);

    /**
     * Delete job by ID.
     *
     * @param int $jobId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($jobId);
}
