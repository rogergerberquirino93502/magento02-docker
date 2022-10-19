<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Locale;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Locale\ListsInterface;

/**
 * Class Options
 */
class Options implements OptionSourceInterface
{
    /**
     * @var ListsInterface
     */
    protected $localeLists;

    /**
     * @var array
     */
    protected $options;

    /**
     * Options constructor.
     * @param ListsInterface $localeLists
     */
    public function __construct(ListsInterface $localeLists)
    {
        $this->localeLists = $localeLists;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $this->options = $this->localeLists->getTranslatedOptionLocales();

        return $this->options;
    }
}
