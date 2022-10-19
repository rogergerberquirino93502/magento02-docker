<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Replacement\Option;

use Magento\Framework\Data\OptionSourceInterface;
use Firebear\ImportExport\Exception\AttributePoolException;

/**
 * Class AttributePool
 * @package Firebear\ImportExport\Model\Import\Replacement\Option
 */
class AttributePool
{
    /**
     * @var array
     */
    private $options;

    /**
     * AttributePool constructor.
     * @param array $options
     */
    public function __construct(
        $options = []
    ) {
        $this->options = $options;
    }

    /**
     * @return array
     * @throws AttributePoolException
     */
    public function getAllOptions()
    {
        $options = [];
        /**@var $option OptionSourceInterface*/
        foreach ($this->options as $attributeCode => $option) {
            if (!($option instanceof OptionSourceInterface)) {
                throw new AttributePoolException(__('Incorrect instance of option object'));
            }
            $options[$attributeCode] = $option->toOptionArray();
        }
        return $options;
    }
}
