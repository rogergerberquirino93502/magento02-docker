<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Replace\Attribute\CatalogRule;

use Magento\CatalogRule\Api\Data\RuleInterface;
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
    private $catalogRuleOptions = [
        RuleInterface::RULE_ID => 'Rule ID',
        RuleInterface::NAME => 'Rule Name',
        RuleInterface::DESCRIPTION => 'Description',
        RuleInterface::IS_ACTIVE => 'Enable Rule',
        RuleInterface::STOP_RULES_PROCESSING => 'Stop Rules Processing',
        RuleInterface::SORT_ORDER => 'Sort Order',
        RuleInterface::DISCOUNT_AMOUNT => 'Discount Amount'
    ];

    /**
     * @return array
     */
    private function getCatalogRuleOptions()
    {
        foreach ($this->catalogRuleOptions as $value => $label) {
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
        return $this->getCatalogRuleOptions();
    }
}
