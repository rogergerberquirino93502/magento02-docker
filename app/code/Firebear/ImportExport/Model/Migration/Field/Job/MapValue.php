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
class MapValue implements JobInterface
{
    /**
     * @var array
     */
    protected $mapping;

    /**
     * @var bool
     */
    protected $allowEmpty;

    /**
     * @param array $mapping
     * @param bool $allowEmpty
     */
    public function __construct(array $mapping, bool $allowEmpty = false)
    {
        $this->mapping = $mapping;
        $this->allowEmpty = $allowEmpty;
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
        if ($this->allowEmpty && empty($sourceValue)) {
            return $sourceValue;
        }

        if (isset($this->mapping[$sourceValue])) {
            return $this->mapping[$sourceValue];
        }

        throw new LocalizedException(__("Mapping not found for the value %1.", $sourceValue));
    }
}
