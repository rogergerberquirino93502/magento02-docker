<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Context;
use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;
use Firebear\ImportExport\Model\Import\Platforms;

/**
 * Class LoadPlatform
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Job
 */
class LoadPlatform extends JobController
{
    /**
     * @var Platforms
     */
    protected $_platforms;

    /**
     * LoadPlatform constructor.
     *
     * @param Context $context
     * @param Platforms $platforms
     */
    public function __construct(
        Context $context,
        Platforms $platforms
    ) {
        parent::__construct($context);
        $this->_platforms = $platforms;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create($this->resultFactory::TYPE_JSON);
        if ($this->getRequest()->isAjax()) {
            $entityType = $this->getRequest()->getParam('entity');
            $platformList = $this->_platforms->getPlatformList($entityType);
            return $resultJson->setData($platformList);
        }
    }
}
