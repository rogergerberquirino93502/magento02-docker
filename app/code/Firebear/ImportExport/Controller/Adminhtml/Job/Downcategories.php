<?php

/**
 * @copyright: Copyright © 2018 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Context;
use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;
use Firebear\ImportExport\Ui\Component\Form\Categories\Options as CategoryOptions;

/**
 * Class Downcategories
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Job
 */
class Downcategories extends JobController
{
    /**
     * @var CategoryOptions
     */
    protected $categories;

    /**
     * Downcategories constructor.
     *
     * @param Context $context
     * @param CategoryOptions $categories
     */
    public function __construct(
        Context $context,
        CategoryOptions $categories
    ) {
        parent::__construct($context);
        $this->categories = $categories;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create($this->resultFactory::TYPE_JSON);
        if ($this->getRequest()->isAjax()) {
            $options = $this->categories->toOptionArray();

            return $resultJson->setData($options);
        }
        return $resultJson->setData([]);
    }
}
