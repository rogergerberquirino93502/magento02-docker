<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration\FilterJobs;

/**
 * @inheritdoc
 */
class WebsiteId implements FilterJobsInterface
{
    /**
     * @var array
     */
    protected $websites = [];

    /**
     * @param array $websites
     */
    public function __construct(array $websites)
    {
        $this->websites = $websites;
    }

    /**
     * @return array
     */
    public function getWebsiteIds()
    {
        return $this->websites;
    }

    /**
     * @inheritdoc
     */
    public function apply($field, $select)
    {
        $select->where("{$field} IN (?)", $this->websites);
    }
}
