<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Api\Import;

use Firebear\ImportExport\Api\Import\GetBunchCountInterface;
use Firebear\ImportExport\Model\ResourceModel\Import\Data as DataResource;

/**
 * Job run command (Service Provider Interface - SPI)
 *
 * @api
 */
class GetBunchCount implements GetBunchCountInterface
{
    /**
     * @var DataResource
     */
    private $dataResource;

    /**
     * Initialize command
     *
     * @param DataResource $dataResource
     */
    public function __construct(
        DataResource $dataResource
    ) {
        $this->dataResource = $dataResource;
    }

    /**
     * Get job bunch count
     *
     * @param int $jobId
     * @param string $file
     * @return int
     */
    public function execute($jobId, $file)
    {
        return (int)$this->dataResource->getCount($jobId, $file);
    }
}
