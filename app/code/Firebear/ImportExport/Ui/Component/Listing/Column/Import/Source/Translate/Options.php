<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Translate;

use Firebear\ImportExport\Helper\DictionaryLanguages;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Options
 * @package Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Translate
 */
class Options implements OptionSourceInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var \Firebear\ImportExport\Helper\DictionaryLanguages
     */
    protected $dictionary;

    /**
     * Options constructor.
     *
     * @param \Firebear\ImportExport\Helper\DictionaryLanguages $dictionary
     */
    public function __construct(
        DictionaryLanguages $dictionary
    ) {
        $this->dictionary = $dictionary;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->options === null) {
            $options[] = ['label' => __('Select'), 'value' => ''];

            $list = $this->dictionary->getLanguages();
            foreach ($list as $code => $label) {
                $options[] = ['label' => $label, 'value' => $code];
            }
            $this->options = $options;
        }
        return $this->options;
    }
}
