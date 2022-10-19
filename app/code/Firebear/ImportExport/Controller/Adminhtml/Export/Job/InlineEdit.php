<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Export\Job;

/**
 * Class InlineEdit
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Export\Job
 */
class InlineEdit extends \Firebear\ImportExport\Controller\Adminhtml\Export\Job
{
    /**
     * @return mixed
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create($this->resultFactory::TYPE_JSON);
        $error      = false;
        $messages   = [];
        $postItems  = $this->getRequest()->getParam('items', []);
        if (!($this->getRequest()->getParam('isAjax') && !empty($postItems))) {
            return $resultJson->setData(
                [
                    'messages' => [__('Please correct the data sent.')],
                    'error' => true,
                ]
            );
        }
        foreach ($postItems as $item) {
            $jobId = $item['entity_id'];
            try {
                if ($jobId) {
                    $model = $this->repository->getById($jobId);
                    $model->addData($item);
                    $this->repository->save($model);
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $messages[] = $e->getMessage();
                $error      = true;
            } catch (\RuntimeException $e) {
                $messages[] = $e->getMessage();
                $error      = true;
            } catch (\Exception $e) {
                $messages[] = __('Something went wrong while saving the export job.');
                $error      = true;
            }
        }

        return $resultJson->setData(
            [
                'messages' => $messages,
                'error' => $error
            ]
        );
    }
}
