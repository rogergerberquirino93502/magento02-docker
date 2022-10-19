<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\ResourceModel\Job;

use Firebear\ImportExport\Model\Job as ModelJob;
use Firebear\ImportExport\Model\ResourceModel\AbstractCollection;
use Firebear\ImportExport\Model\ResourceModel\Job as ResourceModelJob;
use Magento\Framework\DataObject;

/**
 * Class Collection
 *
 * @package Firebear\ImportExport\Model\ResourceModel\Job
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
            ModelJob::class,
            ResourceModelJob::class
        );
    }

    /**
     * Let do something before add loaded item in collection
     *
     * @param DataObject $item
     * @return DataObject
     */
    protected function beforeAddLoadedItem(DataObject $item)
    {
        $this->_resource->unserialize($item);
        $this->_resource->decrypt($item);

        return $item;
    }
}
