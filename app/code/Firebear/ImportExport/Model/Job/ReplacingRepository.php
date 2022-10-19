<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Job;

use Firebear\ImportExport\Api\Data\JobReplacingInterface;
use Firebear\ImportExport\Api\JobReplacingRepositoryInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class ReplacingRepository
 * @package Firebear\ImportExport\Model\Job
 */
class ReplacingRepository implements JobReplacingRepositoryInterface
{
    /**
     * @var \Firebear\ImportExport\Model\Job\ReplacingFactory
     */
    protected $factory;
    /**
     * @var \Firebear\ImportExport\Model\ResourceModel\Job\Replacing
     */
    private $resource;

    /**
     * @param \Firebear\ImportExport\Model\ResourceModel\Job\Replacing $resource
     * @param \Firebear\ImportExport\Model\Job\ReplacingFactory $factory
     */
    public function __construct(
        \Firebear\ImportExport\Model\ResourceModel\Job\Replacing $resource,
        \Firebear\ImportExport\Model\Job\ReplacingFactory $factory
    ) {
        $this->resource = $resource;
        $this->factory = $factory;
    }

    /**
     * @param int $id
     * @return JobReplacingInterface
     * @throws NoSuchEntityException
     */
    public function getById($id)
    {
        $model = $this->factoryModel();
        $this->resource->load($model, $id);
        if (!$model->getId()) {
            throw new NoSuchEntityException(__('Replacing model with id "%1" does not exist.', $id));
        }
        return $model;
    }

    /**
     * @param JobReplacingInterface|Replacing $model
     * @return JobReplacingInterface
     * @throws CouldNotSaveException
     */
    public function save(JobReplacingInterface $model)
    {
        try {
            $this->resource->save($model);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __('Could not save the replacing: %1', $e->getMessage())
            );
        }
        return $model;
    }

    /**
     * @param JobReplacingInterface|Replacing $model
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(JobReplacingInterface $model)
    {
        try {
            $this->resource->delete($model);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(
                __('Could not delete the map: %1', $e->getMessage())
            );
        }
        return true;
    }

    /**
     * @param int $id
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById($id)
    {
        return $this->delete($this->getById($id));
    }

    /**
     * @return JobReplacingInterface|Replacing
     */
    private function factoryModel()
    {
        return $this->factory->create();
    }
}
