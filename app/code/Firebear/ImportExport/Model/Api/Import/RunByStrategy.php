<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Api\Import;

use Firebear\ImportExport\Api\Data\ImportInterface;
use Firebear\ImportExport\Api\Import\RunByIdInterface;
use Firebear\ImportExport\Api\Import\RunByStrategyInterface;
use Firebear\ImportExport\Model\Job\Strategy\StrategyPoolInterface;
use Firebear\ImportExport\Model\Job\Strategy\StrategyInterface;
use Firebear\ImportExport\Helper\Data as Helper;
use Firebear\ImportExport\Model\Job\Processor;

/**
 * Run job by strategy (Service Provider Interface - SPI)
 *
 * @api
 */
class RunByStrategy implements RunByStrategyInterface
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
     * @var StrategyPoolInterface
     */
    private $strategyPool;

    /**
     * @var RunByIdInterface
     */
    private $runById;

    /**
     * Initialize
     *
     * @param StrategyPoolInterface $strategyPool
     * @param RunByIdInterface $runById
     * @param Processor $processor
     * @param Helper $helper
     */
    public function __construct(
        StrategyPoolInterface $strategyPool,
        RunByIdInterface $runById,
        Processor $processor,
        Helper $helper
    ) {
        $this->strategyPool = $strategyPool;
        $this->runById = $runById;
        $this->processor = $processor;
        $this->helper = $helper;
    }

    /**
     * Run job by strategy
     *
     * @param ImportInterface $job
     * @param string $type
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(ImportInterface $job, $type = 'webapi')
    {
        $this->processor->inConsole = 1;
        $this->processor->debugMode = $this->helper->getDebugMode();
        $this->processor->setLogger($this->helper->getLogger());
        /* reset import model */
        $this->processor->getImportModel(true);

        $strategy = $this->getStrategy($job);
        $result = true;
        if ($strategy instanceof StrategyInterface) {
            $strategy->rewind();
            while ($strategy->valid()) {
                $result = $this->runById->execute((int)$job->getEntityId(), $type);
                $strategy->setLastResult($result);
                $strategy->next();
            }
        }
        return $result;
    }

    /**
     * Return the strategy
     *
     * @param ImportInterface $job
     * @return StrategyInterface|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getStrategy(ImportInterface $job)
    {
        /** @var StrategyInterface $strategy */
        foreach ($this->strategyPool->getStrategiesInstances() as $strategy) {
            $strategy->setJob($job);
            if ($strategy->isAvailable()) {
                return $strategy;
            }
        }
        return null;
    }
}
