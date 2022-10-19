<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Firebear\ImportExport\Model\Source\Config;

/**
 * Class Options
 */
class Options implements OptionSourceInterface
{
    const FILE = 'file';

    /**
     * @var array
     */
    protected $options;

    /**
     * @var \Firebear\ImportExport\Model\Source\Config
     */
    protected $config = null;

    /**
     * Options constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $types = $this->config->get();
        $sources[] = ['label' => __('-- Please Select --'), 'value' => ''];
        foreach ($types as $typeName => $type) {
            $sources[] = [
                'label' => $type['label'],
                'value' => $typeName,
                'depends' => ($type['depends']) ? explode(',', $type['depends']) : $type['depends'],
                'api' => isset($type['api']) && $type['api'] === '1'? "1" : "0"
            ];
        }

        $this->options = $sources;

        return $this->options;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $types = $this->config->get();
        foreach ($types as $typeName => $type) {
            $sources[] = [$typeName => $type['label']];
        }

        $this->options = $sources;
    }
}
