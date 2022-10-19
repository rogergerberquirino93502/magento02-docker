<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\FileFormat;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Options
 */
class Options implements OptionSourceInterface
{
    /**
     * @var \Magento\ImportExport\Model\Export\ConfigInterface
     */
    protected $exportConfig;

    public function __construct(
        \Firebear\ImportExport\Model\Source\Type\File\Config $exportConfig
    ) {
        $this->exportConfig = $exportConfig;
    }

    /**
     * @var array
     */
    protected $options;

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $entities = $this->exportConfig->get();
        $options = [];
        foreach ($entities['export'] as $key => $item) {
            $options[] = [
                'label' => $item['label'],
                'value' => $key
            ];
        }
        $this->options = $options;

        return $this->options;
    }
}
