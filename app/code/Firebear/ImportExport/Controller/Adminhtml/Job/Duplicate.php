<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;
use Firebear\ImportExport\Model\ResourceModel\Job\Mapping\Collection as MappingCollection;

/**
 * Class Duplicate
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Job
 */
class Duplicate extends JobController
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
                $job = $this->repository->getById($jobId);
                $job->setId(null);
                $job->setTitle('Duplicate of ' . $job->getTitle());
                $newJob =  $this->repository->save($job);

                if ($newJob->getId()) {
                    $mapping = $this->_objectManager->create(MappingCollection::class);
                    $mapping->addFieldToFilter("job_id", $jobId);
                    if ($mapping->count()) {
                        foreach ($mapping as $key => $item) {
                            $item->setId(null);
                            $item->setJobId($newJob->getId());
                            $item->save();
                        }
                    }
                }

                // display success message
                $this->messageManager->addSuccessMessage(__('The job has been duplicated.'));
                // go to grid

                return $resultRedirect->setPath('*/*/edit', ['entity_id' => $newJob->getId()]);
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addErrorMessage($e->getMessage());
                // go back to edit form
                return $resultRedirect->setPath('*/*/edit', ['entity_id' => $jobId]);
            }
        }
        // display error message
        $this->messageManager->addErrorMessage(__('We can\'t find a job to duplicate.'));
        // go to grid
        return $resultRedirect->setPath('*/*/');
    }
}
