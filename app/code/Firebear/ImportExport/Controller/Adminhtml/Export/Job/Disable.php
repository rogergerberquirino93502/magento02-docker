<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Export\Job;

use Firebear\ImportExport\Api\Data\ExportInterface;

/**
 * Class Disable
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Export\Job
 */
class Disable extends \Firebear\ImportExport\Controller\Adminhtml\Export\Job
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
            try {
                $job = $this->repository->getById($jobId);
                $job->setIsActive(ExportInterface::STATUS_DISABLED);
                $this->repository->save($job);
                $this->messageManager->addSuccessMessage(__('The job changed status.'));

                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addErrorMessage($e->getMessage());
                // go back to edit form
                return $resultRedirect->setPath('*/*/');
            }
        }
        // display error message
        $this->messageManager->addErrorMessage(__('We can\'t find a job to сhange status.'));
        // go to grid
        return $resultRedirect->setPath('*/*/');
    }
}
