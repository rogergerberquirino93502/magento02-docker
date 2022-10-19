<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;

/**
 * Class Index
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Job
 */
class Index extends JobController
{
    public function execute()
    {
        $resultPage = $this->resultFactory->create($this->resultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Firebear_ImportExport::import_job')
            ->addBreadcrumb(__('Import Jobs'), __('Import Jobs'));
        $resultPage->getConfig()->getTitle()->prepend(__('Import Jobs'));
        return $resultPage;
    }
}
