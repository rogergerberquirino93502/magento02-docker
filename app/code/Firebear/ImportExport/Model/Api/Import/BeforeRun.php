<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Api\Import;

use Firebear\ImportExport\Api\Import\BeforeRunInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Job before run command (Service Provider Interface - SPI)
 *
 * @api
 */
class BeforeRun implements BeforeRunInterface
{
    /**
     * @var TimezoneInterface
     */
    private $timeZone;

    /**
     * Initialize command
     *
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        TimezoneInterface $timezone
    ) {
        $this->timeZone = $timezone;
    }

    /**
     * Retrieve file name
     *
     * @param int $jobId
     * @return string
     */
    public function execute($jobId)
    {
        $date = $this->timeZone->date();
        return $jobId . '-' . $date->getTimestamp();
    }
}
