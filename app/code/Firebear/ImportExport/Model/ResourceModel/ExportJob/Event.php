<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\ResourceModel\ExportJob;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Event extends AbstractDb
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('firebear_export_jobs_event', 'event');
    }
}
