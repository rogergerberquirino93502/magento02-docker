<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Entity;

use Magento\Framework\Data\OptionSourceInterface;
use Firebear\ImportExport\Model\Import;
use Magento\ImportExport\Model\Source\Import\Behavior\Factory;
use Firebear\ImportExport\Model\Source\Import\Config;

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
     * @var Import
     */
    protected $import;

    /**
     * @var Config
     */
    protected $importConfig;

    /**
     * Options constructor.
     * @param Import $import
     * @param Config $importConfig
     */
    public function __construct(
        Import $import,
        Config $importConfig
    ) {
        $this->import = $import;
        $this->importConfig = $importConfig;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $listKeys = [];
        $uniqueBehaviors = $this->import->getUniqueEntityBehaviors();

        foreach ($uniqueBehaviors as $behaviorCode => $behaviorClass) {
            if (!isset($listKeys[$behaviorClass])) {
                $listKeys[$behaviorClass] = $behaviorCode;
            }
        }

        $options = [];
        $options[] = ['label' => __('-- Please Select --'), 'value' => '', 'code' => null];
        foreach ($this->importConfig->getEntities() as $entityName => $entityConfig) {
            $options[] = [
                'label' => __($entityConfig['label']),
                'value' => $entityName,
                'code' => $listKeys[$entityConfig['behaviorModel']]
            ];
        }
        return $options;
    }
}
