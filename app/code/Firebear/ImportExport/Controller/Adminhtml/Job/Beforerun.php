<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;

/**
 * Class Beforerun
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Job
 */
class Beforerun extends JobController
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
            $id = $this->getRequest()->getParam('id');
            $file = $this->helper->beforeRun($id);
            return $resultJson->setData($file);
        }
    }
}
