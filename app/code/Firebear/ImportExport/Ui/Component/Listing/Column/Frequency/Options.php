<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Frequency;

use Magento\Framework\Data\OptionSourceInterface;
use Firebear\ImportExport\Model\JobFactory;

/**
 * Class Options
 */
class Options implements OptionSourceInterface
{
    /**
     * @var JobFactory
     */
    protected $jobFactory;

    /**
     * @var array
     */
    protected $options;

    /**
     * Options constructor.
     *
     * @param JobFactory $jobFactory
     */
    public function __construct(JobFactory $jobFactory)
    {
        $this->jobFactory = $jobFactory;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $this->options = $this->jobFactory->create()->getExtendedFrequencyModes();

        return $this->options;
    }
}
