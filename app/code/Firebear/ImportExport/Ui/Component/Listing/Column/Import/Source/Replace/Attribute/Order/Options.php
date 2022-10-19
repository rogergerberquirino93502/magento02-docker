<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Replace\Attribute\Order;

use Magento\Framework\Data\OptionSourceInterface;
use Firebear\ImportExport\Model\Import\ImportAdapterInterface;
use Firebear\ImportExport\Model\Import\Order;

/**
 * Class Options
 *
 * @package Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Replace\Attribute
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Options implements OptionSourceInterface
{
    /**
     * @var array
     */
    private $options;

    /**
     * @var ImportAdapterInterface
     */
    private $order;

    /**
     * @param Order $order
     */
    public function __construct(
        Order $order
    ) {
        $this->order = $order;
    }

    /**
     * @return array
     */
    private function getOrderOptions()
    {
        $this->options = [];
        foreach ($this->order->getReplacingFields() as $field) {
            $this->options[] = ['label' => $field, 'value' => $field];
        }
        return $this->options;
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        if (null === $this->options) {
            $this->options = $this->getOrderOptions();
        }
        return $this->options;
    }
}
