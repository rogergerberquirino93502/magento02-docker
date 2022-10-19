<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Api\Import;

use Firebear\ImportExport\Api\Import\ReindexInterface;
use Firebear\ImportExport\Helper\Data as Helper;
use Psr\Log\LoggerInterface;

/**
 * Reindex command (Service Provider Interface - SPI)
 *
 * @api
 */
class Reindex implements ReindexInterface
{
    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Initialize command
     *
     * @param LoggerInterface $logger
     * @param Helper $helper
     * @param Sender $sender
     */
    public function __construct(
        LoggerInterface $logger,
        Helper $helper
    ) {
        $this->logger = $logger;
        $this->helper = $helper;
    }

    /**
     * Run import
     *
     * @param int $jobId
     * @param string $file
     * @return bool
     */
    public function execute($jobId, $file)
    {
        $result = false;
        try {
            $result = $this->helper->processReindex($file, $jobId);
        } catch (\Exception $e) {
            $result = false;
            $this->logger->critical($e);
        }
        return $result;
    }
}
