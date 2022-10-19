<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Api\Data\ImportInterface;
use Firebear\ImportExport\Controller\Adminhtml\AbstractMass;

/**
 * Class MassDisable
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Job
 */
class MassDisable extends AbstractMass
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
            $job->setIsActive(ImportInterface::STATUS_DISABLED);
            $this->repository->save($job);
        }

        return $this->getRedirect('A total of %1 record(s) have been disabled.', $size);
    }
}
