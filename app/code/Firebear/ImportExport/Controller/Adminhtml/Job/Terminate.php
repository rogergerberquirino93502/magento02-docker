<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Magento\Framework\Controller\ResultInterface;
use Firebear\ImportExport\Controller\Adminhtml\Context;
use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;
use Firebear\ImportExport\Model\Job\Handler\HandlerPoolInterface;
use Firebear\ImportExport\Model\Job\Handler\HandlerInterface;
use Firebear\ImportExport\Model\Job\Processor;

/**
 * Terminate controller
 */
class Terminate extends JobController
{
    /**
     * @var HandlerPoolInterface
     */
    private $handlerPool;

    /**
     * @var Processor
     */
    private $processor;

    /**
     * Initialize
     *
     * @param Context $context
     * @param Processor $processor
     * @param HandlerPoolInterface $handlerPool
     */
    public function __construct(
        Context $context,
        Processor $processor,
        HandlerPoolInterface $handlerPool
    ) {
        $this->processor = $processor;
        $this->handlerPool = $handlerPool;

        parent::__construct($context);
    }

    /**
     * @return ResultInterface
     */
    public function execute()
    {
        if (!$this->getRequest()->isAjax()) {
            $this->_forward('noroute');
            return;
        }

        try {
            $file = $this->getRequest()->getParam('file');
            $jobId = (int)$this->getRequest()->getParam('job');
            $result = (int)$this->getRequest()->getParam('status');

            $this->processor->inConsole = 0;
            $this->processor->debugMode = $this->helper->getDebugMode();
            $this->processor->setLogger($this->helper->getLogger());

            $data = $this->processor->prepareJob($jobId);
            $this->processor->getImportModel()->setData($data);
            $this->processor->getImportModel()->getLogger()->setFileName($file);
            /** @var HandlerInterface $handler */
            foreach ($this->handlerPool->getHandlersInstances() as $handler) {
                $handler->execute($this->processor->getJob(), $file, $result);
            }
        } catch (\Exception $e) {
            $result = false;
        }

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create($this->resultFactory::TYPE_JSON);
        return $resultJson->setData(['result' => $result]);
    }
}
