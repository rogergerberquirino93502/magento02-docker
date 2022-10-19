<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Strategy;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;

/**
 * Class Options
 */
class Options implements OptionSourceInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $this->options = [
            [
                'label' => __('Stop on Error'),
                'value' =>ProcessingErrorAggregatorInterface::VALIDATION_STRATEGY_STOP_ON_ERROR
            ],
            [
                'label' => __('Skip error entries'),
                'value' => ProcessingErrorAggregatorInterface::VALIDATION_STRATEGY_SKIP_ERRORS
            ]
        ];

        return $this->options;
    }

    /**
     * @return array
     */
    public function toArray()
    {

        return [
            ProcessingErrorAggregatorInterface::VALIDATION_STRATEGY_STOP_ON_ERROR => __('Stop on Error'),
            ProcessingErrorAggregatorInterface::VALIDATION_STRATEGY_SKIP_ERRORS => __('Skip error entries')

        ];
    }
}
