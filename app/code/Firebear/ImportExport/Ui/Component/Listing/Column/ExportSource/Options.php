<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\ExportSource;

use Magento\Framework\Data\OptionSourceInterface;
use Firebear\ImportExport\Model\Source\Export\Config;

/**
 * Class Options
 */
class Options implements OptionSourceInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var \Firebear\ImportExport\Model\Source\Export\Config
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
            if (isset($type['depends'])) {
                $sources[] = [
                    'label' => $type['label'],
                    'value' => $typeName,
                    'depends' => explode(',', $type['depends']),
                    'api' => isset($type['api']) && $type['api'] === '1' ? '1' : '0'
                ];
            } else {
                $sources[] = [
                    'label' => $type['label'],
                    'value' => $typeName,
                    'depends' => $type['depends'],
                    'api' => isset($type['api']) && $type['api'] === '1' ? '1' : '0'
                ];
            }
        }

        $this->options = $sources;

        return $this->options;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $sources = [];
        $types = $this->config->get();
        foreach ($types as $typeName => $type) {
            $sources[$typeName] = $type['label'];
        }
        return $this->options = $sources;
    }
}
