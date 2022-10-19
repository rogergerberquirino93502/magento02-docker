<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Export\Job;

/**
 * Class Delete
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Export\Job
 */
class Delete extends \Firebear\ImportExport\Controller\Adminhtml\Export\Job
{
    /**
     * @return mixed
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $jobId = $this->getRequest()->getParam('entity_id');
        if ($jobId) {
            try {
                $model = $this->repository->getById($jobId);
                $this->repository->delete($model);
                $this->messageManager->addSuccessMessage(__('You deleted the job.'));

                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['entity_id' => $jobId]);
            }
        }
        // display error message
        $this->messageManager->addErrorMessage(__('We can\'t find a job to delete.'));
    }
}
