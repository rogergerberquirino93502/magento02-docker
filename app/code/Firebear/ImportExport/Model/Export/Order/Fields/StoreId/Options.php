<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export\Order\Fields\StoreId;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Options
 */
class Options implements OptionSourceInterface
{
    /**
     * @var \Magento\Store\Model\ResourceModel\Store\Collection
     */
    protected $collection;

    /**
     * @var array
     */
    protected $options;

    public function __construct(
        \Magento\Store\Model\ResourceModel\Store\Collection $collection
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
            $options[] = ['label' => $item->getName(), 'value' => $item->getId()];
        }
        $this->options = $options;

        return $this->options;
    }
}
