<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration;

use Magento\Framework\Exception\LocalizedException;

/**
 * @api
 */
interface PreJobInterface
{
    /**
     * Do a pre processing before data migration
     *
     * @throws LocalizedException
     */
    public function job();
}
