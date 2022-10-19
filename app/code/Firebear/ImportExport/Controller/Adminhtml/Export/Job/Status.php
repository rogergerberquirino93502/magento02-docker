<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Export\Job;

use Firebear\ImportExport\Controller\Adminhtml\Export\Job as JobController;

/**
 * Class Status
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Export\Job
 */
class Status extends JobController
{
    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create($this->resultFactory::TYPE_JSON);
        if ($this->getRequest()->isAjax()) {
            //read required fields from xml file
            $file = $this->getRequest()->getParam('file');
            $counter = (int)$this->getRequest()->getParam('number', 0);
            $console = $this->helper->scopeRun($file, $counter);

            return $resultJson->setData(
                [
                    'console' => $console
                ]
            );
        }
    }
}
