<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\ResourceModel\Job\Replacing;

use Firebear\ImportExport\Api\Data\JobReplacingInterface;
use Firebear\ImportExport\Model\Job\Replacing as ModelReplacing;
use Firebear\ImportExport\Model\ResourceModel\AbstractCollection;
use Firebear\ImportExport\Model\ResourceModel\Job\Replacing as ResourceModelReplacing;

/**
 * Class Collection
 * @package Firebear\ImportExport\Model\ResourceModel\Job\Replacing
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = JobReplacingInterface::ENTITY_ID;

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            ModelReplacing::class,
            ResourceModelReplacing::class
        );
    }
}
