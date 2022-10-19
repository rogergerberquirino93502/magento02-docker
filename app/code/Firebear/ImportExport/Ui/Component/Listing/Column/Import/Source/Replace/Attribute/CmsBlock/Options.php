<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Replace\Attribute\CmsBlock;

use Magento\Cms\Api\Data\BlockInterface;
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
    private $cmsBlockOptions = [
        BlockInterface::BLOCK_ID => 'Block ID',
        BlockInterface::CONTENT => 'Content',
        BlockInterface::CREATION_TIME => 'Creation Time',
        BlockInterface::IDENTIFIER => 'Identifier',
        BlockInterface::IS_ACTIVE => 'Enable Block',
        BlockInterface::TITLE => 'Block Title',
        BlockInterface::UPDATE_TIME => 'Update Time'
    ];

    /**
     * @return array
     */
    private function getCmsPageOptions()
    {
        foreach ($this->cmsBlockOptions as $value => $label) {
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
        return $this->getCmsPageOptions();
    }
}
