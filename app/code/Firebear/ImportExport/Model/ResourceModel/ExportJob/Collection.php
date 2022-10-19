<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\ResourceModel\ExportJob;

use Firebear\ImportExport\Model\ExportJob as ModelExportJob;
use Firebear\ImportExport\Model\ResourceModel\ExportJob as ResourceModelExportJob;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\DataObject;

/**
 * Class Collection
 *
 * @package Firebear\ImportExport\Model\ResourceModel\ExportJob
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            ModelExportJob::class,
            ResourceModelExportJob::class
        );
    }

    /**
     * Process adding event names to result collection
     *
     * @return $this
     */
    public function addEventToResult()
    {
        $events = [];
        foreach ($this->getItems() as $item) {
            $events[$item->getId()] = [];
        }

        if ($events) {
            $select = $this->getConnection()->select()->from(
                $this->getTable('firebear_export_jobs_event'),
                ['job_id', 'event']
            )->where('job_id IN (?)', array_keys($events));

            $data = $this->getConnection()->fetchAll($select);
            foreach ($data as $row) {
                $events[$row['job_id']][] = $row['event'];
            }
        }

        foreach ($this->getItems() as $item) {
            if (isset($events[$item->getId()])) {
                $item->setData('event', implode(',', $events[$item->getId()]));
            }
        }
        return $this;
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
