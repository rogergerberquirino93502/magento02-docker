<?php

/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Context;
use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;
use Firebear\ImportExport\Exception\AttributePoolException;
use Firebear\ImportExport\Model\Import\Replacement\Option\AttributePool;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\Result\Json;

/**
 * Class Downattributes
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Job
 */
class Downattributes extends JobController
{
    /**
     * @var string
     */
    private $entityType;

    /**
     * @var array
     */
    private $options;

    /**
     * @var string
     */
    private $attributePool;

    /**
     * Downcategories constructor.
     *
     * @param AttributePool $attributePool
     * @param Context $context
     */
    public function __construct(
        AttributePool $attributePool,
        Context $context
    ) {
        $this->attributePool = $attributePool;
        parent::__construct($context);
    }

    /**
     * @return void
     */
    private function setEntityType()
    {
        $this->entityType = $this->getRequest()->getParam('entity_type');
    }

    /**
     * @return ResultInterface
     * @throws AttributePoolException
     */
    public function execute()
    {
        $this->setEntityType();
        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create($this->resultFactory::TYPE_JSON);
        $this->options = [];
        if ($this->getRequest()->isAjax()) {
            $allOptions = $this->attributePool->getAllOptions();
            array_key_exists($this->entityType, $allOptions) ?
                $this->options = $allOptions[$this->entityType] : false;
        }
        return $resultJson->setData($this->options);
    }
}
