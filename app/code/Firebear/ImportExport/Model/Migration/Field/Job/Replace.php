<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration\Field\Job;

use Firebear\ImportExport\Model\Migration\Field\JobInterface;

/**
 * @inheritdoc
 */
class Replace implements JobInterface
{
    /**
     * @var array
     */
    protected $replacements = [];

    /**
     * @param array $replacements
     */
    public function __construct(array $replacements)
    {
        $this->replacements = $replacements;
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
        foreach ($this->replacements as $item) {
            $pattern = $item['pattern'];
            $replacement = $item['replacement'];
            $sourceValue = preg_replace($pattern, $replacement, $sourceValue);
        }

        return $sourceValue;
    }
}
