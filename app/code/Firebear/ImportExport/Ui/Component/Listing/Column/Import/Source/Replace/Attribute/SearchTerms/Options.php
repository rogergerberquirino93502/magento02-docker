<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Replace\Attribute\SearchTerms;

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
    private $searchTermsOptions = [
        'query_text' => 'Query Text',
        'updated_at' => 'Update Time'
    ];

    /**
     * @return array
     */
    private function getSearchTermsOptions()
    {
        foreach ($this->searchTermsOptions as $value => $label) {
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
        return $this->getSearchTermsOptions();
    }
}
