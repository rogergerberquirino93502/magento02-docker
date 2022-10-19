<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Api;

use Firebear\ImportExport\Api\Data\ImportMappingInterface;

/**
 * Interface JobRepositoryInterface
 *
 * @package Firebear\ImportExport\Api
 */
interface JobMappingRepositoryInterface
{
    /**
     * Save map.
     *
     * @param ImportMappingInterface $map
     * @return ImportMappingInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(ImportMappingInterface $map);

    /**
     * Get map by id.
     *
     * @param int $id
     * @return ImportMappingInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($id);

    /**
     * Delete map.
     *
     * @param ImportMappingInterface $map
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(ImportMappingInterface $map);

    /**
     * Delete job by id.
     *
     * @param int $id
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($id);
}
