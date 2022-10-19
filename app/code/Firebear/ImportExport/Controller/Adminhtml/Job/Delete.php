<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;

/**
 * Class Delete
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Job
 */
class Delete extends JobController
{
    /**
     * Delete a job
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        // check if we know what should be deleted
        $jobId = $this->getRequest()->getParam('entity_id');
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($jobId) {
            $title = "";
            try {
                // init model and delete
                $this->repository->deleteById($jobId);
                // display success message
                $this->messageManager->addSuccessMessage(__('The job has been deleted.'));
                // go to grid
                $this->_eventManager->dispatch(
                    'adminhtml_importjob_on_delete',
                    ['title' => $title, 'status' => 'success']
                );
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->_eventManager->dispatch(
                    'adminhtml_importjob_on_delete',
                    ['title' => $title, 'status' => 'fail']
                );
                // display error message
                $this->messageManager->addErrorMessage($e->getMessage());
                // go back to edit form
                return $resultRedirect->setPath('*/*/edit', ['entity_id' => $jobId]);
            }
        }
        // display error message
        $this->messageManager->addErrorMessage(__('We can\'t find a job to delete.'));
        // go to grid
        return $resultRedirect->setPath('*/*/');
    }
}
