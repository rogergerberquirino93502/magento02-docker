<?php

namespace Firebear\ImportExport\Model\Migration\Field\Job;

use Firebear\ImportExport\Model\Migration\Field\JobInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * @inheritdoc
 */
class CopyValueDateTime implements JobInterface
{
    /**
     * @var string
     */
    protected $field;

    /**
     * @param string $field
     */
    public function __construct(string $field)
    {
        $this->field = $field;
    }

    /**
     * @inheritdoc
     */
    public function job(
        $sourceField,
        $sourceValue,
        $destinationFiled,
        $destinationValue,
        $sourceDataRow
    ) {
        if (isset($sourceDataRow[$this->field])) {
            if (strtotime($sourceDataRow[$this->field]) !== false) {
                return strtotime($sourceDataRow[$this->field]);
            }
        }

        throw new LocalizedException(__("Source field %1 does not exists.", $this->field));
    }
}
