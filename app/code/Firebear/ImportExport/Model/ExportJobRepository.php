<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Validation\ValidationException;
use Firebear\ImportExport\Api\Export\SaveInterface;
use Firebear\ImportExport\Api\ExportJobRepositoryInterface;
use Firebear\ImportExport\Model\ResourceModel\ExportJob as ExportJobResource;
use Firebear\ImportExport\Model\ExportJobFactory;
use Firebear\ImportExport\Model\ResourceModel\ExportJob\CollectionFactory as ExportCollectionFactory;

/**
 * Class ExportJobRepository
 *
 * @package Firebear\ImportExport\Model
 */
class ExportJobRepository implements ExportJobRepositoryInterface
{
    /**
     * @var ExportJobResource
     */
    protected $resource;

    /**
     * @var \Firebear\ImportExport\Model\ExportJobFactory
     */
    protected $exportFactory;

    /**
     * @var ExportCollectionFactory
     */
    protected $exportCollectionFactory;

    /**
     * @var SaveInterface
     */
    private $saveCommand;

    /**
     * ExportJobRepository constructor.
     *
     * @param ExportJobResource $resource
     * @param \Firebear\ImportExport\Model\ExportJobFactory $exportFactory
     * @param ExportCollectionFactory $exportCollectionFactory
     * @param SaveInterface $saveCommand
     */
    public function __construct(
        ExportJobResource $resource,
        ExportJobFactory $exportFactory,
        ExportCollectionFactory $exportCollectionFactory,
        SaveInterface $saveCommand
    ) {
        $this->resource = $resource;
        $this->exportFactory = $exportFactory;
        $this->exportCollectionFactory = $exportCollectionFactory;
        $this->saveCommand = $saveCommand;
    }

    /**
     * @param \Firebear\ImportExport\Api\Data\ExportInterface $job
     *
     * @return \Firebear\ImportExport\Api\Data\ExportInterface
     * @throws CouldNotSaveException
     * @throws ValidationException
     */
    public function save(\Firebear\ImportExport\Api\Data\ExportInterface $job)
    {
        return $this->saveCommand->execute($job);
    }

    /**
     * @param $jobId
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getById($jobId)
    {
        $job = $this->exportFactory->create();
        $this->resource->load($job, $jobId);
        if (!$job->getId()) {
            throw new NoSuchEntityException(__('Job with id "%1" does not exist.', $jobId));
        }

        return $job;
    }

    /**
     * @param \Firebear\ImportExport\Api\Data\ExportInterface $job
     *
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(\Firebear\ImportExport\Api\Data\ExportInterface $job)
    {
        try {
            $this->resource->delete($job);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __(
                    'Could not delete the job: %1',
                    $exception->getMessage()
                )
            );
        }

        return true;
    }

    /**
     * @param $jobId
     *
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById($jobId)
    {
        return $this->delete($this->getById($jobId));
    }
}
