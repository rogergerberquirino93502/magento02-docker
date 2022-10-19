<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export\Order\Fields\Status;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Options
 */
class Options implements OptionSourceInterface
{
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Status\Collection
     */
    protected $collection;

    /**
     * @var array
     */
    protected $options;

    /**
     * Options constructor.
     * @param \Magento\Sales\Model\ResourceModel\Order\Status\Collection $collection
     */
    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\Status\Collection $collection
    ) {
        $this->collection = $collection;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        foreach ($this->collection as $item) {
            $options[] = ['label' => $item->getLabel(), 'value' => $item->getStatus()];
        }
        $this->options = $options;

        return $this->options;
    }
}
