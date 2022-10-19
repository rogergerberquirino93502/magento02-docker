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
class SoapVersion implements OptionSourceInterface
{

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'SOAP_1_1', 'label' => 'SOAP_1_1'],
            ['value' => 'SOAP_1_2', 'label' => 'SOAP_1_2'],
        ];
    }
}
