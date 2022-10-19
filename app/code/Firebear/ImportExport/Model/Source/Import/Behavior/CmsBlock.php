<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Import\Behavior;

use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Source\Import\AbstractBehavior;

/**
 * Class CmsBlock
 *
 * @package Firebear\ImportExport\Model\Source\Import\Behavior
 */
class CmsBlock extends AbstractBehavior
{
    const  ONLY_UPDATE = 'update';

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            Import::BEHAVIOR_APPEND => __('Add'),
            Import::BEHAVIOR_DELETE => __('Delete'),
            Import::BEHAVIOR_REPLACE => __('Replace'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return 'cms_block';
    }
}
