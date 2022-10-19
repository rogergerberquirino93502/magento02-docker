<?php
/**
 * RestMethod
 *
 * @copyright Copyright © 2018 Firebear Studio. All rights reserved.
 * @author    Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\ExportJob\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class IsActive
 */
class RestMethod implements OptionSourceInterface
{

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'PUT', 'label' => 'PUT'],
            ['value' => 'DELETE', 'label' => 'DELETE'],
        ];
    }
}
