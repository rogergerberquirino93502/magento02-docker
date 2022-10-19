<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Export;

use Firebear\ImportExport\Api\ExportJobRepositoryInterface;
use Firebear\ImportExport\Model\ExportJobFactory;
use Firebear\ImportExport\Helper\Data as Helper;
use Magento\Backend\App\Action\Context as NativeContext;

/**
 * Class Context
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Export
 */
class Context
{
    /**
     * @var NativeContext
     */
    protected $context;

    /**
     * @var ExportJobFactory
     */
    protected $jobFactory;

    /**
     * @var ExportJobRepositoryInterface
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
     * @param ExportJobFactory $jobFactory
     * @param ExportJobRepositoryInterface $repository
     * @param Helper $helper
     */
    public function __construct(
        NativeContext $context,
        ExportJobFactory $jobFactory,
        ExportJobRepositoryInterface $repository,
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
     * @return ExportJobFactory
     */
    public function getJobFactory()
    {
        return $this->jobFactory;
    }

    /**
     * @return ExportJobRepositoryInterface
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
