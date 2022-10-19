<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Api;

use Firebear\ImportExport\Api\Data\ExportEventInterface;

/**
 * Interface JobEventRepositoryInterface
 *
 * @package Firebear\ImportExport\Api
 */
interface JobEventRepositoryInterface
{
    /**
     * Save Event.
     *
     * @param ExportEventInterface $event
     * @return ExportEventInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(ExportEventInterface $event);

    /**
     * Get event by id.
     *
     * @param int $jobId
     * @return ExportEventInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($jobId);

    /**
     * Delete event.
     *
     * @param ExportEventInterface $event
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(ExportEventInterface $event);

    /**
     * Delete event by id.
     *
     * @param int $jobId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($jobId);
}
