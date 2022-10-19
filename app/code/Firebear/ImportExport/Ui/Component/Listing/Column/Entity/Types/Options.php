<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Entity\Types;

use Firebear\ImportExport\Model\Export\Dependencies\Config;
use Firebear\ImportExport\Model\ExportFactory;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\ImportExport\Model\Export\ConfigInterface;
use Magento\ImportExport\Model\Source\Export\Entity;

/**
 * Class Options
 */
class Options implements OptionSourceInterface
{
    /**
     * @var ConfigInterface
     */
    protected $exportConfig;

    /**
     * @var Config
     */
    protected $diExport;

    /**
     * @var array
     */
    protected $options;

    /**
     * Options constructor.
     * @param ConfigInterface $exportConfig
     * @param Config $configExDi
     */
    public function __construct(
        ConfigInterface $exportConfig,
        Config $configExDi
    ) {
        $this->exportConfig = $exportConfig;
        $this->diExport = $configExDi;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $options = [];
            foreach ($this->exportConfig->getEntities() as $entityName => $entityConfig) {
                $options[$entityName] = [['value' => $entityName, 'label' => __($entityConfig['label'])]];
            }
            $data = $this->diExport->get();
            foreach ($data as $typeName => $type) {
                $childs = [];
                if (isset($type['fields'])) {
                    foreach ($type['fields'] as $name => $field) {
                        $childs[] = ['label' => $field['label'], 'value' => $name, 'dep' => $typeName];
                    }
                } else {
                    $childs[] = ['label' => $type['label'], 'value' => $typeName];
                }
                $options[$typeName] = $childs;
            }
            $this->options = $options;
        }
        return $this->options;
    }
}
