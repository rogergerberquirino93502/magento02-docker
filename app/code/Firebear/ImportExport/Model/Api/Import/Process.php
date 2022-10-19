<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Api\Import;

use Firebear\ImportExport\Api\Import\ProcessInterface;
use Firebear\ImportExport\Api\Import\ProcessResponseInterface;
use Firebear\ImportExport\Model\Job\Processor;
use Firebear\ImportExport\Model\Job\ProcessorFactory;
use Firebear\ImportExport\Helper\Data as Helper;

/**
 * Job process command (Service Provider Interface - SPI)
 *
 * @api
 */
class Process implements ProcessInterface
{
    /**
     * @var ProcessorFactory
     */
    private $processorFactory;

    /**
     * @var Processor
     */
    private $processor;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var ProcessResponseInterface
     */
    private $response;

    /**
     * Initialize command
     *
     * @param ProcessorFactory $processorFactory
     * @param ProcessResponseInterface $response
     * @param Helper $helper
     */
    public function __construct(
        ProcessorFactory $processorFactory,
        ProcessResponseInterface $response,
        Helper $helper
    ) {
        $this->processorFactory = $processorFactory;
        $this->response = $response;
        $this->helper = $helper;
    }

    /**
     * Process import
     *
     * @param int $jobId
     * @param string $file
     * @param int $offset
     * @param string $error
     * @return ProcessResponseInterface
     */
    public function execute($jobId, $file, $offset, $error)
    {
        list($count, $result) = $this->getProcessor()->processImport($file, $jobId, $offset, $error);
        $this->response->setCount($count);
        $this->response->setResult($result);
        return $this->response;
    }

    /**
     * Retrieve import processor
     *
     * @return Processor
     */
    private function getProcessor()
    {
        if (null === $this->processor) {
            $this->processor = $this->processorFactory->create();
            $this->processor->setDebugMode($this->helper->getDebugMode());
            $this->processor->setLogger($this->helper->getLogger());
        }
        return $this->processor;
    }
}
