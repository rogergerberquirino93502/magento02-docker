<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Export;

use Firebear\ImportExport\Model\ResourceModel\ExportJob\CollectionFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Class AbstractMass
 *
 * @package Firebear\ImportExport\Controller\Adminhtml\Export
 */
class AbstractMass extends Job
{
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * AbstractMass constructor.
     *
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);

        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return mixed
     */
    protected function getCollection()
    {
        return $this->filter->getCollection($this->collectionFactory->create());
    }

    /**
     * @param $message
     * @param $size
     *
     * @return mixed
     */
    protected function getRedirect($message, $size)
    {
        $this->messageManager->addSuccessMessage(__($message, $size));

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        return $resultRedirect->setPath('*/*/');
    }

    public function execute()
    {
        return true;
    }
}
