<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration;

use Magento\Framework\ObjectManagerInterface;

/**
 * @api
 */
class MigrationJobFactory
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param string $entity
     *
     * @return JobInterface
     */
    public function create(string $entity)
    {
        $class = "\\Firebear\\ImportExport\\Model\\Migration\\{$entity}";
        $job = $this->objectManager->create($class);

        if (!($job instanceof JobInterface)) {
            throw new \InvalidArgumentException(
                "Migration job for {$entity} not found."
            );
        }

        return $job;
    }
}
