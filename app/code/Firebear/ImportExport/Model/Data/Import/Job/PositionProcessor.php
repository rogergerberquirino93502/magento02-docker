<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Data\Import\Job;

use Firebear\ImportExport\Model\Data\ProcessorInterface;
use Firebear\ImportExport\Api\Data\ImportInterface;

/**
 * Position processor
 */
class PositionProcessor implements ProcessorInterface
{
    /**
     * Process entity data
     *
     * @param array $data
     * @return array
     */
    public function process($data)
    {
        $position = $data[ImportInterface::POSITION] ?? null;
        if (!empty($position) && is_numeric($position)) {
            $position = (int)$position;
        } else {
            $position = null;
        }
        $data[ImportInterface::POSITION] = $position;

        return $data;
    }
}
