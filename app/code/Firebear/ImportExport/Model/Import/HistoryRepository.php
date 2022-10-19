<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import;

use Firebear\ImportExport\Api\Data\ImportHistoryInterface;
use Firebear\ImportExport\Api\HistoryRepositoryInterface;
use Firebear\ImportExport\Logger\Logger;
use Firebear\ImportExport\Model\ResourceModel\Import\History as ImportHistoryResource;
use Firebear\ImportExport\Model\ResourceModel\Import\History\CollectionFactory as ImportCollectionFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class JobRepository
 *
 * @package Firebear\ImportExport\Model
 */
class HistoryRepository implements HistoryRepositoryInterface
{

    /**
     * @var ImportHistoryResource
     */
    protected $resource;

    /**
     * @var HistoryFactory
     */
    protected $importFactory;

    /**
     * @var ImportCollectionFactory
     */
    protected $importCollectionFactory;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * JobRepository constructor.
     *
     * @param ImportHistoryResource $resource
     * @param HistoryFactory $importFactory
     * @param ImportCollectionFactory $importCollectionFactory
     * @param Logger $logger
     */
    public function __construct(
        ImportHistoryResource $resource,
        HistoryFactory $importFactory,
        ImportCollectionFactory $importCollectionFactory,
        Logger $logger
    ) {
        $this->resource = $resource;
        $this->importFactory = $importFactory;
        $this->importCollectionFactory = $importCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function save(ImportHistoryInterface $history)
    {
        try {
            $this->resource->save($history);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the job: %1',
                $exception->getMessage()
            ));
        }

        return $history;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($historyId)
    {
        $history = $this->importFactory->create();
        $this->resource->load($history, $historyId);

        if (!$history->getId()) {
            throw new NoSuchEntityException(__('Import History with id "%1" does not exist.', $historyId));
        }

        return $history;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(ImportHistoryInterface $history)
    {
        try {
            $this->resource->delete($history);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the job: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($historyId)
    {
        return $this->delete($this->getById($historyId));
    }
}
