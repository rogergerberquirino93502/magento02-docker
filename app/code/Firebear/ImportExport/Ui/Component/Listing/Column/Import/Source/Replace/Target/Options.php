<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Replace\Target;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Framework\Data\Collection\AbstractDb;

/**
 * Class Options
 * @package Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Replace\Target
 */
class Options implements OptionSourceInterface
{
    const INDIVIDUAL_WORD = 0;
    const FULL_VALUE      = 1;

    /** @var array */
    private $options;

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        if ($this->options === null) {
            $this->options = $this->makeOptions();
        }
        return $this->options;
    }

    /**
     * @return array
     */
    private function makeOptions()
    {
        return [
            ['value' => self::INDIVIDUAL_WORD, 'label' => __('Individual Word')],
            ['value' => self::FULL_VALUE,      'label' => __('Full Value')],
        ];
    }
}
