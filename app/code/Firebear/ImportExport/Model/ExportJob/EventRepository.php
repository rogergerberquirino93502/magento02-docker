<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

declare(strict_types=1);

namespace Firebear\ImportExport\Model\ExportJob;

use Exception;
use Firebear\ImportExport\Api\Data\ExportEventInterface;
use Firebear\ImportExport\Api\JobEventRepositoryInterface;
use Firebear\ImportExport\Model\ResourceModel\ExportJob\Event as EventResource;
use Firebear\ImportExport\Model\ExportJob\EventFactory;
use Firebear\ImportExport\Model\ResourceModel\ExportJob\Event\CollectionFactory as CollectionFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Class EventRepository
 *
 * @package Firebear\ImportExport\Model\Job
 */
class EventRepository implements JobEventRepositoryInterface
{

    protected $resource;

    protected $factory;

    protected $collectionFactory;

    public function __construct(
        EventResource $resource,
        EventFactory $factory,
        CollectionFactory $collectionFactory
    ) {
        $this->resource                = $resource;
        $this->factory           = $factory;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Save Event.
     *
     * @param ExportEventInterface $event
     * @return ExportEventInterface
     * @throws CouldNotSaveException
     */
    public function save(ExportEventInterface $event)
    {
        try {
            $this->resource->save($event);
        } catch (Exception $exception) {
            throw new CouldNotSaveException(
                __(
                    'Could not save the event: %1',
                    $exception->getMessage()
                )
            );
        }

        return $event;
    }

    /**
     * Get event by id.
     *
     * @param int $jobId
     * @return ExportEventInterface
     */
    public function getById($jobId)
    {
        $object = $this->factory->create();
        $this->resource->load($object, $jobId);
        return $object;
    }

    /**
     * Delete event.
     *
     * @param ExportEventInterface $event
     * @return bool true on success
     * @throws CouldNotDeleteException
     */
    public function delete(ExportEventInterface $event)
    {
        try {
            $this->resource->delete($event);
        } catch (Exception $exception) {
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
     * Delete event by id.
     *
     * @param int $jobId
     * @return bool true on success
     * @throws CouldNotDeleteException
     */
    public function deleteById($jobId)
    {
        return $this->delete($this->getById($jobId));
    }
}
