<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Registry for \Magento\Customer\Model\Customer
 */
class JobRegistry
{
    const REGISTRY_SEPARATOR = ':';

    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * @var array
     */
    private $jobRegistryById = [];

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * Constructor
     *
     * @param JobFactory $jobFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        JobFactory $jobFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->jobFactory = $jobFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Retrieve Job Model from registry given an id
     *
     * @param string $jobId
     * @return Job
     * @throws NoSuchEntityException
     */
    public function retrieve($jobId)
    {
        if (isset($this->jobRegistryById[$jobId])) {
            return $this->jobRegistryById[$jobId];
        }
        /** @var Customer $customer */
        $job = $this->jobFactory->create()->load($jobId);
        if (!$job->getId()) {
            // job does not exist
            throw NoSuchEntityException::singleField('jobId', $jobId);
        } else {
            $this->jobRegistryById[$jobId] = $job;
            return $job;
        }
    }

    /**
     * Remove instance of the Job Model from registry given an id
     *
     * @param int $jobId
     * @return void
     */
    public function remove($jobId)
    {
        if (isset($this->jobRegistryById[$jobId])) {
            /** @var Customer $customer */
            unset($this->jobRegistryById[$jobId]);
        }
    }

    /**
     * Replace existing job model with a new one.
     *
     * @param Job $job
     * @return $this
     */
    public function push(Job $job)
    {
        $this->jobRegistryById[$job->getId()] = $job;
        return $this;
    }
}
