<?php
/**
 * Options
 *
 * @copyright Copyright © 2020 Firebear Studio. All rights reserved.
 * @author    Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Replace\Attribute;

use Firebear\ImportExport\Model\Import\Replacement\Option\AttributePool;
use Firebear\ImportExport\Exception\AttributePoolException;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Options
 * @package Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Replace\Attribute
 */
class Options implements OptionSourceInterface
{
    /**
     * @var AttributePool
     */
    private $attributes;

    /**
     * Options constructor.
     *
     * @param AttributePool $attributes
     */
    public function __construct(AttributePool $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array
     * @throws AttributePoolException
     */
    public function toOptionArray()
    {
        $allOptions = [];
        foreach ($this->attributes->getAllOptions() as $attributeCode => $options) {
            foreach ($options as &$option) {
                if (isset($option['value'])) {
                    $option['value'] =  $option['value'] . '_' . $attributeCode;
                    $allOptions []= $option;
                }
                continue;
            }
        }
        return $allOptions;
    }
}
