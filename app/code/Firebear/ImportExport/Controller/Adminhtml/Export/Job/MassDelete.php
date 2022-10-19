<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Export\Job;

use Firebear\ImportExport\Controller\Adminhtml\Export\AbstractMass;

/**
 * Class MassDelete
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Export\Job
 */
class MassDelete extends AbstractMass
{
    /**
     * Execute action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     */
    public function execute()
    {
        $collection  = $this->getCollection();
        $size = $collection->getSize();

        foreach ($collection as $job) {
            $this->repository->delete($job);
        }

        return $this->getRedirect('A total of %1 record(s) have been deleted.', $size);
    }
}
