<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Replace\Attribute\ImportJobs;

use Firebear\ImportExport\Api\Data\ImportInterface;
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
    private $importJobsOptions = [
        ImportInterface::TITLE => 'Job Title',
        ImportInterface::IS_ACTIVE => 'Job Enabled',
        ImportInterface::CRON => 'Cron',
        ImportInterface::FREQUENCY => 'Frequency',
        ImportInterface::TRANSLATE_FROM => 'Translate From',
        ImportInterface::TRANSLATE_TO => 'Translate To'
    ];

    /**
     * @return array
     */
    private function getImportJobsOptions()
    {
        foreach ($this->importJobsOptions as $value => $label) {
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
        return $this->getImportJobsOptions();
    }
}
