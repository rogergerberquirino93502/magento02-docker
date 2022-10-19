<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\ResourceModel\Import\History;

use Firebear\ImportExport\Model\Import\History as ModelHistory;
use Firebear\ImportExport\Model\ResourceModel\AbstractCollection;
use Firebear\ImportExport\Model\ResourceModel\Import\History as ResourceModelHistory;

/**
 * Class Collection
 *
 * @package Firebear\ImportExport\Model\ResourceModel\Import\History
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'history_id';

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            ModelHistory::class,
            ResourceModelHistory::class
        );
    }
}
