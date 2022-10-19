<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Export;

use Firebear\ImportExport\Api\ExportJobRepositoryInterface;
use Firebear\ImportExport\Model\ExportJobFactory;
use Firebear\ImportExport\Helper\Data as Helper;
use Magento\Backend\App\Action;

/**
 * Class Job
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Export
 */
abstract class Job extends Action
{
    const ADMIN_RESOURCE = 'Firebear_ImportExport::export_job';

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
     * Job constructor.
     *
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        parent::__construct(
            $context->getContext()
        );

        $this->jobFactory = $context->getJobFactory();
        $this->repository = $context->getRepository();
        $this->helper = $context->getHelper();
    }
}
