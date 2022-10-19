<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\ResourceModel\Job\Mapping;

use Firebear\ImportExport\Model\Job\Mapping as ModelMapping;
use Firebear\ImportExport\Model\ResourceModel\AbstractCollection;
use Firebear\ImportExport\Model\ResourceModel\Job\Mapping as ResourceModelMapping;

/**
 * Class Collection
 *
 * @package Firebear\ImportExport\Model\ResourceModel\Job\Mapping
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            ModelMapping::class,
            ResourceModelMapping::class
        );
    }
}
