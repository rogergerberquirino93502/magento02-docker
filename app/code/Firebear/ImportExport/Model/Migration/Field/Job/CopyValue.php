<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration\Field\Job;

use Firebear\ImportExport\Model\Migration\Field\JobInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * @inheritdoc
 */
class CopyValue implements JobInterface
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
    public function job($sourceField, $sourceValue, $destinationFiled, $destinationValue, $sourceDataRow)
    {
        if (isset($sourceDataRow[$this->field])) {
            return $sourceDataRow[$this->field];
        }

        throw new LocalizedException(__("Source field %1 does not exists.", $this->field));
    }
}
