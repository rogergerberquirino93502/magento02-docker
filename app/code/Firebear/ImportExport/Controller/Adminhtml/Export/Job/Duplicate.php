<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Export\Job;

/**
 * Class Duplicate
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Export\Job
 */
class Duplicate extends \Firebear\ImportExport\Controller\Adminhtml\Export\Job
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
                $model->setId(null);
                $model->setTitle('Duplicate of ' . $model->getTitle());
                /* clean file path */
                $source = $model->getExportSource();
                if (isset($source['export_source_file_file_path'])) {
                    $source['export_source_file_file_path'] = '';
                }
                $model->setExportSource($source);

                $newJob =  $this->repository->save($model);
                $this->messageManager->addSuccessMessage(__('You duplicate the job.'));

                return $resultRedirect->setPath('*/*/edit', ['entity_id' => $newJob->getId()]);
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['entity_id' => $jobId]);
            }
        }
        // display error message
        $this->messageManager->addErrorMessage(__('We can\'t find a job to duplicate.'));
    }
}
