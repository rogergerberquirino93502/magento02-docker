<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration\FilterJobs;

/**
 * @inheritdoc
 */
class StoreId implements FilterJobsInterface
{
    /**
     * @var array
     */
    protected $stores = [];

    /**
     * @param array $stores
     */
    public function __construct(array $stores)
    {
        $this->stores = $stores;
    }

    /**
     * @return array
     */
    public function getStoreIds()
    {
        return $this->stores;
    }

    /**
     * @inheritdoc
     */
    public function apply($field, $select)
    {
        $select->where("{$field} IN (?)", $this->stores);
    }
}
