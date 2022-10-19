<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Job\Source;

use Firebear\ImportExport\Model\Source\ConfigInterface;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class ImportSource
 * @package Firebear\ImportExport\Model\Job\Source
 */
class ImportSource implements OptionSourceInterface
{

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * ImportSource constructor.
     *
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $types = $this->config->get();
        foreach ($types as $typeName => $type) {
            $sources[] = ['label' => $type['label'], 'value' => $typeName];
        }

        return $sources;
    }
}
