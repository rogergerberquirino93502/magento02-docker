<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Cron;

use Magento\Cron\Model\Groups\Config\Data;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Options
 */
class GroupOptions implements OptionSourceInterface
{
    /**
     * @var array
     */
    protected $options;
    /**
     * @var Data
     */
    private $groupsConfig;

    /**
     * Options constructor.
     * @param Data $groupsConfig
     */
    public function __construct(
        Data $groupsConfig
    ) {
        $this->groupsConfig = $groupsConfig;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $groups = $this->groupsConfig->get();
        foreach (array_keys($groups) as $groupName) {
            $this->options[] = [
                'label' => $groupName,
                'value' => $groupName
            ];
        }
        return $this->options;
    }
}
