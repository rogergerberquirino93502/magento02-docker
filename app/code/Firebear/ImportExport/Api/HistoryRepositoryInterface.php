<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Api;

use Firebear\ImportExport\Api\Data\ImportHistoryInterface;

/**
 * Interface JobRepositoryInterface
 *
 * @package Firebear\ImportExport\Api
 */
interface HistoryRepositoryInterface
{
    /**
     * Save job.
     *
     * @param ImportHistoryInterface $history
     * @return ImportHistoryInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(ImportHistoryInterface $history);

    /**
     * Get history by id.
     *
     * @param int $id
     * @return ImportHistoryInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($id);

    /**
     * Delete history.
     *
     * @param ImportHistoryInterface $history
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(ImportHistoryInterface $history);

    /**
     * Delete history by id.
     *
     * @param int $historyId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($historyId);
}
