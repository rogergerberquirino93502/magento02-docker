<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Api;

use Firebear\ImportExport\Api\Data\JobReplacingInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interface JobReplacingRepositoryInterface
 * @package Firebear\ImportExport\Api
 */
interface JobReplacingRepositoryInterface
{
    /**
     * @param int $id
     * @return JobReplacingInterface
     * @throws LocalizedException
     */
    public function getById($id);

    /**
     * @param JobReplacingInterface $model
     * @return JobReplacingInterface
     * @throws LocalizedException
     */
    public function save(JobReplacingInterface $model);

    /**
     * @param JobReplacingInterface $model
     * @return bool
     * @throws LocalizedException
     */
    public function delete(JobReplacingInterface $model);

    /**
     * @param int $id
     * @return bool
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById($id);
}
