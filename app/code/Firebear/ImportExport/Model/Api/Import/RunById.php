<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Api\Import;

use Firebear\ImportExport\Api\Data\ImportInterface;
use Firebear\ImportExport\Api\Import\RunByIdInterface;
use Firebear\ImportExport\Model\Job\Processor;
use Firebear\ImportExport\Model\Job\Handler\HandlerPoolInterface;
use Firebear\ImportExport\Model\Job\Handler\HandlerInterface;
use Firebear\ImportExport\Model\Source\Type\SearchSourceTypeInterface;
use Firebear\ImportExport\Helper\Data as Helper;

/**
 * Run job by id (Service Provider Interface - SPI)
 *
 * @api
 */
class RunById implements RunByIdInterface
{
    /**
     * @var Processor
     */
    private $processor;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var HandlerPoolInterface
     */
    private $handlerPool;

    /**
     * Initialize
     *
     * @param Processor $processor
     * @param HandlerPoolInterface $handlerPool
     * @param Helper $helper
     */
    public function __construct(
        Processor $processor,
        HandlerPoolInterface $handlerPool,
        Helper $helper
    ) {
        $this->processor = $processor;
        $this->handlerPool = $handlerPool;
        $this->helper = $helper;
    }

    /**
     * Run job by id
     *
     * @param int $jobId
     * @param string $type
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute($jobId, $type = 'webapi')
    {
        $file = $this->helper->beforeRun($jobId);
        $history = $this->helper->createHistory($jobId, $file, $type);

        $this->processor->inConsole = 1;
        $this->processor->debugMode = $this->helper->getDebugMode();
        $this->processor->setLogger($this->helper->getLogger());

        $status = $this->processor->processScope($jobId, $file);
        if ($status) {
            $errorCount = 0;
            $bunchCount = $this->helper->countData($file, $jobId);
            for ($i = 0; $i < $bunchCount; $i++) {
                $this->processor->getImportModel()->getErrorAggregator()->clear();
                list($count, $status) = $this->helper->processImport($file, $jobId, $i, $errorCount);
                $errorCount += $count;
                if (!$status) {
                    break;
                }
            }
        }

        $this->helper->saveFinishHistory($history);
        /** @var HandlerInterface $handler */
        foreach ($this->handlerPool->getHandlersInstances() as $handler) {
            $handler->execute($this->processor->getJob(), $file, (int)$status);
        }
        return $status;
    }
}
