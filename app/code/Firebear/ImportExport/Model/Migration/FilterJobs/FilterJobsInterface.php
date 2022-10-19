<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration\FilterJobs;

use Magento\Framework\DB\Select;

/**
 * @api
 */
interface FilterJobsInterface
{
    /**
     * @param string $field
     * @param Select $select
     */
    public function apply($field, $select);
}
