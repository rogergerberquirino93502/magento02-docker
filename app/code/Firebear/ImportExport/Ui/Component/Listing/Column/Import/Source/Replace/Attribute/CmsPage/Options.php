<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Replace\Attribute\CmsPage;

use Magento\Cms\Api\Data\PageInterface;
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
    private $cmsPageOptions = [
        PageInterface::PAGE_ID => 'Page ID',
        PageInterface::IDENTIFIER => 'Identifier',
        PageInterface::TITLE => 'Page Title',
        PageInterface::PAGE_LAYOUT => 'Page Layout',
        PageInterface::META_TITLE => 'Meta Title',
        PageInterface::META_KEYWORDS => 'Meta Keywords',
        PageInterface::META_DESCRIPTION => 'Meta Description',
        PageInterface::CONTENT_HEADING => 'Heading',
        PageInterface::CONTENT => 'Content',
        PageInterface::CREATION_TIME => 'Creation Time',
        PageInterface::UPDATE_TIME => 'Update Time',
        PageInterface::SORT_ORDER => 'Sort Order',
        PageInterface::LAYOUT_UPDATE_XML => 'Layout Update XML',
        PageInterface::CUSTOM_THEME => 'Custom Theme',
        PageInterface::CUSTOM_ROOT_TEMPLATE => 'Custom Root Template',
        PageInterface::CUSTOM_LAYOUT_UPDATE_XML => 'Custom Layout Update XML',
        PageInterface::CUSTOM_THEME_FROM => 'Custom Theme From',
        PageInterface::CUSTOM_THEME_TO => 'Custom Theme To',
        PageInterface::IS_ACTIVE => 'Enable Page'
    ];

    /**
     * @return array
     */
    private function getCmsPageOptions()
    {
        foreach ($this->cmsPageOptions as $value => $label) {
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
