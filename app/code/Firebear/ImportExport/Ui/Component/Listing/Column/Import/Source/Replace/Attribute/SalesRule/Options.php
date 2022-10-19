<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Replace\Attribute\SalesRule;

use Magento\SalesRule\Model\Data\Rule;
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
    private $salesRuleOptions = [
        Rule::KEY_RULE_ID => 'Rule ID',
        Rule::KEY_NAME => 'Rule Name',
        Rule::KEY_DESCRIPTION => 'Description',
        Rule::KEY_IS_ACTIVE => 'Enable Rule',
        Rule::KEY_STOP_RULES_PROCESSING => 'Stop Rules Processing',
        Rule::KEY_SORT_ORDER => 'Sort Order',
        Rule::KEY_SIMPLE_ACTION => 'Simple Action',
        Rule::KEY_USES_PER_CUSTOMER => 'Uses Per Customer',
        Rule::KEY_DISCOUNT_AMOUNT => 'Discount Amount',
        Rule::KEY_DISCOUNT_QTY => 'Discount Qty',
        Rule::KEY_DISCOUNT_STEP => 'Discount Step'
    ];

    /**
     * @return array
     */
    private function getSalesRuleOptions()
    {
        foreach ($this->salesRuleOptions as $value => $label) {
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
        return $this->getSalesRuleOptions();
    }
}
