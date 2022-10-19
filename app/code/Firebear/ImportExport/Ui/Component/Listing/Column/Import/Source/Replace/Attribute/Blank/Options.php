<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Replace\Attribute\Blank;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Options
 *
 * @package Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Replace\Attribute
 */
class Options implements OptionSourceInterface
{
    /**
     * @var array
     */
    private $options;

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        $this->options = [];
        return $this->options;
    }
}
