<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Translate;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class RefererType
 * @package Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Translate
 */
class RefererType implements OptionSourceInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->options === null) {
            $options[] = ['label' => __('None'), 'value' => ''];
            $options[] = ['label' => __('HTTP'), 'value' => 'http'];
            $this->options = $options;
        }
        return $this->options;
    }
}
