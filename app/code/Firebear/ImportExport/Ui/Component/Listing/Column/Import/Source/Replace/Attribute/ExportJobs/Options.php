<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Replace\Attribute\ExportJobs;

use Firebear\ImportExport\Api\Data\ExportInterface;
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
    private $exportJobsOptions = [
        ExportInterface::TITLE => 'Job Title',
        ExportInterface::IS_ACTIVE => 'Job Enabled',
        ExportInterface::CRON => 'Cron',
        ExportInterface::FREQUENCY => 'Frequency'
    ];

    /**
     * @return array
     */
    private function getExportJobsOptions()
    {
        foreach ($this->exportJobsOptions as $value => $label) {
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
        return $this->getExportJobsOptions();
    }
}
