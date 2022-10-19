<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Links;

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
     * @var \Firebear\ImportExport\Model\Import\Platforms
     */
    protected $platforms;

    /**
     * Options constructor.
     *
     * @param \Firebear\ImportExport\Model\Import\Platforms $platforms
     */
    public function __construct(
        \Firebear\ImportExport\Model\Import\Platforms $platforms
    ) {
        $this->platforms = $platforms;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $this->options = $this->platforms->toOptionArrayLinks();

        return $this->options;
    }
}
