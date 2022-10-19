<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Observer;

use Firebear\ImportExport\Helper\Data as Helper;
use Firebear\ImportExport\Model\ExportJob;
use Firebear\ImportExport\Model\ExportJob\Processor;
use Firebear\ImportExport\Model\ExportJobFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Spout message observer
 */
class ExportJobObserver implements ObserverInterface
{
    /**
     * Export factory
     *
     * @var ExportJobFactory
     */
    protected $_exportJobFactory;

    /**
     * Resource Connection
     *
     * @var ResourceConnection
     */
    protected $_resource;

    /**
     * Export processor
     *
     * @var Processor
     */
    protected $_processor;

    /**
     * Helper
     *
     * @var Helper
     */
    protected $_helper;

    /**
     * Initialize observer
     *
     * @param ExportJobFactory $exportJobFactory
     * @param ResourceConnection $resource
     * @param Processor $processor
     * @param Helper $helper
     */
    public function __construct(
        ExportJobFactory $exportJobFactory,
        ResourceConnection $resource,
        Processor $processor,
        Helper $helper
    ) {
        $this->_exportJobFactory = $exportJobFactory;
        $this->_resource = $resource;
        $this->_processor = $processor;
        $this->_helper = $helper;
    }

    /**
     * Run export job
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $name = $observer->getEvent()->getName();

        $collection = $this->_exportJobFactory->create()->getCollection();
        $collection->getSelect()->join(
            ['ev' => $this->_resource->getTableName('firebear_export_jobs_event')],
            'main_table.entity_id = ev.job_id',
            []
        )->where('ev.event = ?', $name);

        /** @var ExportJob $job */
        foreach ($collection as $job) {
            if ($job->getIsActive()) {
                $file = $this->_helper->beforeRun($job->getId());
                $history = $this->_helper->createExportHistory($job->getId(), $file, 'event');
                $this->_processor->debugMode = $this->_helper->getDebugMode();
                $this->_processor->setLogger($this->_helper->getLogger());
                $this->_processor->inConsole = 1;
                $this->_processor->process($job->getId(), $history);
                $this->_helper->saveFinishExHistory($history);
            }
        }
    }
}
