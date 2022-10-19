<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Export\Job;

/**
 * Class Add
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Export\Job
 */
class Add extends \Firebear\ImportExport\Controller\Adminhtml\Export\Job
{
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
        $resultForward = $this->resultFactory->create($this->resultFactory::TYPE_FORWARD);
        return $resultForward->forward('edit');
    }
}
