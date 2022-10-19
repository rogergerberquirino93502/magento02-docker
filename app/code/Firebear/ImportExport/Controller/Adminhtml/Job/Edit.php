<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Context;
use Magento\Framework\Registry;

/**
 * Class Edit
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Job
 */
class Edit extends \Firebear\ImportExport\Controller\Adminhtml\Job
{
    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * Edit constructor.
     *
     * @param Context $context
     * @param Registry $coreRegistry
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry
    ) {
        parent::__construct($context);
        $this->coreRegistry = $coreRegistry;
    }

    /**
     * @return $this|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $jobId = $this->getRequest()->getParam('entity_id');
        $model = $this->jobFactory->create();
        if ($jobId) {
            $model = $this->repository->getById($jobId);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This job is no longer exists.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();

                return $resultRedirect->setPath('*/*/');
            }
        }

        $this->coreRegistry->register('import_job', $model);

        $resultPage = $this->resultFactory->create($this->resultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Firebear_ImportExport::import_job');
        $resultPage->getConfig()->getTitle()->prepend(__('Import Jobs'));
        $resultPage->addBreadcrumb(__('Import'), __('Import'));
        $resultPage->addBreadcrumb(
            $jobId ? __('Edit Job') : __('New Job'),
            $jobId ? __('Edit Job') : __('New Job')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Jobs'));
        $resultPage->getConfig()->getTitle()->prepend($model->getId() ? $model->getTitle() : __('New Job'));

        return $resultPage;
    }
}
