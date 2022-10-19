<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Export\Job;

/**
 * Class Index
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Export\Job
 */
class Index extends \Firebear\ImportExport\Controller\Adminhtml\Export\Job
{
    public function execute()
    {
        $resultPage = $this->resultFactory->create($this->resultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Firebear_ImportExport::export_job')
            ->addBreadcrumb(__('Export Jobs'), __('Export Jobs'));
        $resultPage->getConfig()->getTitle()->prepend(__('Export Jobs'));

        return $resultPage;
    }
}
