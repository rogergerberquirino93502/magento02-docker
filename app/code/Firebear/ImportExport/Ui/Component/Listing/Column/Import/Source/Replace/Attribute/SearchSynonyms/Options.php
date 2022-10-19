<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Replace\Attribute\SearchSynonyms;

use Magento\Framework\Data\OptionSourceInterface;
use Firebear\ImportExport\Model\Export\SearchSynonyms\SynonymsInterface;

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
    private $searchSynonymsOptions = [
        SynonymsInterface::SYNONYMS => 'Synonyms'
    ];

    /**
     * @return array
     */
    private function getSearchSynonymsOptions()
    {
        foreach ($this->searchSynonymsOptions as $value => $label) {
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
        return $this->getSearchSynonymsOptions();
    }
}
