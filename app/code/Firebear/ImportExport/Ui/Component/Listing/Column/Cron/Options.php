<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Cron;

use Magento\Framework\Data\OptionSourceInterface;

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
          ['label' => __('Minutes'), 'value' => ''],
          ['label' => __('Hours'), 'value' => ''],
          ['label' => __('Days'), 'value' => ''],
          ['label' => __('Months'), 'value' => ''],
          ['label' => __('Days of Week'), 'value' => '']
        ];

        return $this->options;
    }
}
