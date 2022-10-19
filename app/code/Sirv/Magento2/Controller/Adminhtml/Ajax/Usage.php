<?php

namespace Sirv\Magento2\Controller\Adminhtml\Ajax;

/**
 * Usage dada ajax controller
 *
 * @author    Sirv Limited <support@sirv.com>
 * @copyright Copyright (c) 2018-2022 Sirv Limited <support@sirv.com>. All rights reserved
 * @license   https://sirv.com/
 * @link      https://sirv.com/integration/magento/
 */
class Usage extends \Sirv\Magento2\Controller\Adminhtml\Settings
{
    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        /** @var \Sirv\Magento2\Helper\Data\Backend $dataHelper */
        $dataHelper = $this->getDataHelper();

        $result = $dataHelper->getApiLimitsData();

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);
        $resultJson->setData($result);

        return $resultJson;
    }
}
