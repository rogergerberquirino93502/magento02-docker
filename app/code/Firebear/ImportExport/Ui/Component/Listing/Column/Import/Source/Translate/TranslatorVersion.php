<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Translate;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class TranslatorVersion
 * @package Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Translate
 */
class TranslatorVersion implements OptionSourceInterface
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
            $options[] = ['label' => __('Google Free'), 'value' => 'google_free'];
            $options[] = ['label' => __('Google Paid'), 'value' => 'google_paid'];
            $this->options = $options;
        }
        return $this->options;
    }
}
