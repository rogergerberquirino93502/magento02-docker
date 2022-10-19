<?php

/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;
use Firebear\ImportExport\Api\Data\ImportInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Cms page grid inline edit controller
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class InlineEdit extends JobController
{
    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create($this->resultFactory::TYPE_JSON);
        $error      = false;
        $messages   = [];

        $postItems = $this->getRequest()->getParam('items', []);
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
                    $job = $this->repository->getById($jobId);
                    $this->validatePost($item, $error, $messages);
                    $job->addData($item);
                    $this->repository->save($job);
                }
            } catch (LocalizedException $e) {
                $messages[] = $this->getErrorWithJobId($job, $e->getMessage());
                $error      = true;
            } catch (\RuntimeException $e) {
                $messages[] = $this->getErrorWithJobId($job, $e->getMessage());
                $error      = true;
            } catch (\Exception $e) {
                $messages[] = $this->getErrorWithJobId(
                    $job,
                    __('Something went wrong while saving the job.')
                );
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

    /**
     * Add job title to error message
     *
     * @param ImportInterface $job
     * @param string $errorText
     * @return string
     */
    protected function getErrorWithJobId(ImportInterface $job, $errorText)
    {
        return '[Job ID: ' . $job->getId() . '] ' . $errorText;
    }

    /**
     * Validate post data
     *
     * @param [] $jobData
     * @param bool &$error
     * @param [] &$messages
     *
     * @return $this
     */
    protected function validatePost($jobData, &$error, &$messages)
    {
        if (isset($jobData['title'])) {
            $title = trim($jobData['title']);
            if (empty($title)) {
                $error      = true;
                $messages[] = __("Job title can't be empty.");
            }
        }

        return $this;
    }
}
