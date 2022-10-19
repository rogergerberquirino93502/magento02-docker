<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Job;

/**
 * Class Add
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Job
 */
class Add extends Job
{
    /**
     * Create new Job
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
        $resultForward = $this->resultFactory->create($this->resultFactory::TYPE_FORWARD);
        return $resultForward->forward('edit');
    }
}
