<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\TypeFile;

use Magento\Framework\Data\OptionSourceInterface;
use Firebear\ImportExport\Model\Source\Type\File\Config;

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
     * @var Config
     */
    protected $config;

    /**
     * Options constructor.
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $list = [];
        $data = $this->config->get();
        foreach ($data['import'] as $type => $value) {
            $list[] = ['label' => $value['label'], 'value' => $type];
        }

        return $list;
    }
}
