<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Duplicate;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Options
 */
class Options implements OptionSourceInterface
{

    /**
     * @var array
     */
    protected $options;

    /**
     * @var array
     */
    protected $duplicateFields = [
        'product' => ['sku', 'scope', 'url_key'],
        'customer' => [\Magento\CustomerImportExport\Model\Import\Customer::COLUMN_EMAIL],
        'address' => [],
        'composite' => [\Magento\CustomerImportExport\Model\Import\Customer::COLUMN_EMAIL],
        'cmsPage' => []
    ];

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $newOptions = [];
        foreach ($this->duplicateFields as $fields) {
            $newOptions = array_merge($newOptions, $fields);
        }

        $this->options = array_unique($newOptions);

        $options = [];
        foreach ($this->options as $option) {
            $options[] = ['value' => $option, 'label' => $option];
        }

        $this->options = $options;

        return $this->options;
    }
}
