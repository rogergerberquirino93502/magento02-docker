<?php
/**
 * @copyright: Copyright © 2018 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Import\Behavior;

/**
 * Class CmsPage
 *
 * @package Firebear\ImportExport\Model\Source\Import\Behavior
 */
class CmsPage extends \Magento\ImportExport\Model\Source\Import\AbstractBehavior
{
    const  ONLY_UPDATE = 'update';

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            \Magento\ImportExport\Model\Import::BEHAVIOR_APPEND => __('Add'),
            \Magento\ImportExport\Model\Import::BEHAVIOR_DELETE => __('Delete'),
            \Magento\ImportExport\Model\Import::BEHAVIOR_REPLACE => __('Replace'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return 'cms_page';
    }
}
