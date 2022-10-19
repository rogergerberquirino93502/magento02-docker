<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export\SearchSynonyms\AttributeSources;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Search\Ui\Component\Listing\Column\Scope\Options;

/**
 * Class Scope
 *
 * @package Firebear\ImportExport\Model\Export\SearchSynonyms\AttributeSources
 */
class Scope extends AbstractSource
{
    /**
     * @var Options
     */
    private $scopeOptions;

    /**
     * Scope constructor.
     *
     * @param Options $scopeOptions
     */
    public function __construct(
        Options $scopeOptions
    ) {
        $this->scopeOptions = $scopeOptions;
    }

    /**
     * Retrieve all options array
     *
     * @return array
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = $this->scopeOptions->toOptionArray();
        }

        return $this->_options;
    }
}
