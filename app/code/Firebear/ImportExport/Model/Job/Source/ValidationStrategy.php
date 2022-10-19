<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Job\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;

/**
 * Class IsActive
 */
class ValidationStrategy implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            [
                'label' => 'Stop on Error',
                'value' => ProcessingErrorAggregatorInterface::VALIDATION_STRATEGY_STOP_ON_ERROR
            ],
            [
                'label' => 'Skip error entries',
                'value' => ProcessingErrorAggregatorInterface::VALIDATION_STRATEGY_SKIP_ERRORS
            ]
        ];

        return $options;
    }
}
