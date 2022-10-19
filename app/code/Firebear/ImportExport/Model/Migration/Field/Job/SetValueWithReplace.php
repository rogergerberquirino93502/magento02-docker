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
class SetValueWithReplace implements JobInterface
{
    /**
     * @var mixed
     */
    protected $sourceField;

    /**
     * @var mixed
     */
    protected $destinationField;

    /**
     * @param mixed $sourceField
     * @param mixed $destinationField
     */
    public function __construct($sourceField, $destinationField)
    {
        $this->sourceField = $sourceField;
        $this->destinationField = $destinationField;
    }

    /**
     * @inheritdoc
     */
    public function job($sourceField, $sourceValue, $destinationFiled, $destinationValue, $sourceDataRow)
    {
        if (isset($sourceDataRow[$this->sourceField])) {
            if (strtolower(str_replace('', '-', $this->sourceField)) !== false) {
                $destinationFiled = strtolower(str_replace('', '-', $this->sourceField));
                return $destinationFiled;
            }
        }

        throw new LocalizedException(__("Source field %1 does not exists.", $this->sourceField));
    }
}
