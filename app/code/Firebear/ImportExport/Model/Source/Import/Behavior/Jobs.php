<?php
namespace Firebear\ImportExport\Model\Source\Import\Behavior;

use Magento\ImportExport\Model\Import;

/**
 * Class Jobs
 * @package Firebear\ImportExport\Model\Source\Import\Behavior
 */
class Jobs extends Product
{
    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return 'jobs';
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            Import::BEHAVIOR_APPEND => __('Add/Update'),
            self::ONLY_UPDATE => __('Only Update'),
            Import::BEHAVIOR_REPLACE => __('Replace'),
            Import::BEHAVIOR_DELETE => __('Delete')
        ];
    }
}
