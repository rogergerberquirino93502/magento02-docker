<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Job;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Firebear\ImportExport\Api\JobMappingRepositoryInterface;
use Firebear\ImportExport\Model\ResourceModel\Job\Mapping as MappingResource;
use Firebear\ImportExport\Model\Job\MappingFactory;
use Firebear\ImportExport\Model\ResourceModel\Job\Mapping\CollectionFactory as CollectionFactory;
use Firebear\ImportExport\Api\Data\ImportMappingInterface;

/**
 * Class MappingRepository
 *
 * @package Firebear\ImportExport\Model\Job
 */
class MappingRepository implements JobMappingRepositoryInterface
{

    protected $resource;

    protected $factory;

    protected $collectionFactory;

    public function __construct(
        MappingResource $resource,
        MappingFactory $factory,
        CollectionFactory $collectionFactory
    ) {
        $this->resource                = $resource;
        $this->factory           = $factory;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @param ImportMappingInterface $map
     *
     * @return mixed
     * @throws CouldNotSaveException
     */
    public function save(ImportMappingInterface $map)
    {
        try {
            $this->resource->save($map);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __(
                    'Could not save the map: %1',
                    $exception->getMessage()
                )
            );
        }

        return $map;
    }

    /**
     * @param $id
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getById($id)
    {
        $map = $this->factory->create();
        $this->resource->load($map, $id);
        if (!$map->getId()) {
            throw new NoSuchEntityException(__('Map with id "%1" does not exist.', $id));
        }

        return $map;
    }

    /**
     * @param ImportMappingInterface $map
     *
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(ImportMappingInterface $map)
    {
        try {
            $this->resource->delete($map);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __(
                    'Could not delete the map: %1',
                    $exception->getMessage()
                )
            );
        }

        return true;
    }

    /**
     * @param $id
     *
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById($id)
    {
        return $this->delete($this->getById($id));
    }
}
