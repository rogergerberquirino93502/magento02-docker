<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Job\Source;

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
            ['value' => 'POST', 'label' => 'POST'],
            ['value' => 'GET', 'label' => 'GET'],
        ];
    }
}
