<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Platforms;

use Magento\Framework\Registry;
use Magento\Framework\Data\OptionSourceInterface;
use Firebear\ImportExport\Model\Import\Platforms as PlatformConfig;

/**
 * Class Options
 */
class Options implements OptionSourceInterface
{
    /**
     * Options array
     *
     * @var array
     */
    protected $options;

    /**
     * Platform config
     *
     * @var \Firebear\ImportExport\Model\Import\Platforms
     */
    protected $platforms;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * Initialize options
     *
     * @param Registry $registry
     * @param PlatformConfig $platforms
     */
    public function __construct(
        Registry $registry,
        PlatformConfig $platforms
    ) {
        $this->coreRegistry = $registry;
        $this->platforms = $platforms;
    }
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $model = $this->coreRegistry->registry('import_job');
            $options = [['label' => __('None'), 'value' => '']];
            if ($model) {
                $list = $this->platforms->getPlatformList(
                    $model->getEntity()
                );
                $options = array_merge($options, $list['options'] ?? []);
            }
            $this->options = $options;
        }
        return $this->options ?: [];
    }
}
