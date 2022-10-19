<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml;

use Firebear\ImportExport\Api\JobRepositoryInterface;
use Firebear\ImportExport\Model\JobFactory;
use Firebear\ImportExport\Helper\Data as Helper;
use Magento\Backend\App\Action\Context as NativeContext;

/**
 * Class Context
 *
 * @package Firebear\ImportExport\Controller\Adminhtml
 */
class Context
{
    /**
     * @var NativeContext
     */
    protected $context;

    /**
     * @var JobFactory
     */
    protected $jobFactory;

    /**
     * @var JobRepositoryInterface
     */
    protected $repository;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * Context constructor.
     *
     * @param NativeContext $context
     * @param JobFactory $jobFactory
     * @param JobRepositoryInterface $repository
     * @param Helper $helper
     */
    public function __construct(
        NativeContext $context,
        JobFactory $jobFactory,
        JobRepositoryInterface $repository,
        Helper $helper
    ) {
        $this->context = $context;
        $this->jobFactory = $jobFactory;
        $this->repository = $repository;
        $this->helper = $helper;
    }

    /**
     * @return NativeContext
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return JobFactory
     */
    public function getJobFactory()
    {
        return $this->jobFactory;
    }

    /**
     * @return JobRepositoryInterface
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @return Helper
     */
    public function getHelper()
    {
        return $this->helper;
    }
}
