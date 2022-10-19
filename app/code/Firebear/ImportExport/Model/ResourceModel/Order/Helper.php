<?php
/**
 * Helper
 *
 * @copyright Copyright © 2019 Firebear Studio. All rights reserved.
 * @author    fbeardev@gmail.com
 */

namespace Firebear\ImportExport\Model\ResourceModel\Order;

use Magento\Framework\App\ResourceConnection;

/**
 * ImportExport MySQL resource helper model.
 * Extend default for split db functionality.
 *
 * @api
 * @since 100.0.2
 */
class Helper extends \Magento\ImportExport\Model\ResourceModel\Helper
{
    /**
     * @param ResourceConnection $resource
     */
    public function __construct(ResourceConnection $resource)
    {
        parent::__construct($resource, 'sales');
    }
}
