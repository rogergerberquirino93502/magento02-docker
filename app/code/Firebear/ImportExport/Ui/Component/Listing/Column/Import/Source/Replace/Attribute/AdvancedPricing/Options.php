<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Replace\Attribute\AdvancedPricing;

use Magento\Framework\Data\OptionSourceInterface;

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
     * @var array
     */
    private $advancedPricingOptions = [
        'sku' => 'Product Sku',
        'tier_price_qty' => 'Tier Price Qty',
        'tier_price' => 'Tier Price',
        'tier_price_value_type' => 'Tier Price Value Type'
    ];

    /**
     * @return array
     */
    private function getAdvancedPricingOptions()
    {
        foreach ($this->advancedPricingOptions as $value => $label) {
            $label = sprintf('%s (%s)', $value, $label);
            $this->options[] = ['label' => (string)__($label), 'value' => $value];
        }
        return $this->options;
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        return $this->getAdvancedPricingOptions();
    }
}
