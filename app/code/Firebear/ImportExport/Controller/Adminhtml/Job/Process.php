<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;

/**
 * Class Process
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Job
 */
class Process extends JobController
{
    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultJson = $this->resultFactory->create($this->resultFactory::TYPE_JSON);
        if ($this->getRequest()->isAjax()) {
            $file = $this->getRequest()->getParam('file');
            $job = $this->getRequest()->getParam('job');
            $offset = $this->getRequest()->getParam('number', 0);
            $error= $this->getRequest()->getParam('error', 0);
            $this->helper->getProcessor()->inConsole = 0;
            list($count, $result) = $this->helper->processImport($file, $job, $offset, $error);

            return $resultJson->setData(
                [
                    'result' => $result,
                    'count' => $count
                ]
            );
        }
    }
}
