<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Job\Webapi;

use Firebear\ImportExport\Api\Data\ImportInterface;

/**
 * Format the output result
 */
class JobOutputProcessor
{
    /**
     * Fields to restore
     *
     * @var array
     */
    protected $restoredFields = [
        ImportInterface::BEHAVIOR_DATA,
        ImportInterface::SOURCE_DATA
    ];

    /**
     * Format the output result
     *
     * @param ImportInterface $job
     * @param array $result
     * @return array
     */
    public function execute(ImportInterface $job, array $result)
    {
        foreach ($this->restoredFields as $field) {
            $result[$field] = $job->getData($field);
        }
        return $result;
    }
}
