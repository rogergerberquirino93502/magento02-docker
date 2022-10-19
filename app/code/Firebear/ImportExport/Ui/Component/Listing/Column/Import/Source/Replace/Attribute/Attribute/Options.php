<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Replace\Attribute\Attribute;

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
    private $attributeOptions = [
        'attribute_code' => 'Attribute Code',
        'attribute_model' => 'Attribute Model',
        'backend_model' => 'Backend Model',
        'backend_type' => 'Backend Type',
        'frontend_model' => 'Frontend Model',
        'frontend_input' => 'Frontend Input',
        'frontend_label' => 'Frontend Label',
        'source_model' => 'Source Model',
        'note' => 'Note'
    ];

    /**
     * @return array
     */
    private function getAttributeOptions()
    {
        foreach ($this->attributeOptions as $value => $label) {
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
        return $this->getAttributeOptions();
    }
}
