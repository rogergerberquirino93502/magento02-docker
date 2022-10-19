<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Entity\Export;

/**
 * Source export entity model
 */
class Options implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\ImportExport\Model\Export\ConfigInterface
     */
    protected $exportConfig;

    /**
     * @var \Firebear\ImportExport\Model\Export\Dependencies\Config
     */
    protected $diExport;

    /**
     * Entity constructor.
     * @param \Magento\ImportExport\Model\Export\ConfigInterface $exportConfig
     * @param \Firebear\ImportExport\Model\Export\Dependencies\Config $configExDi
     */
    public function __construct(
        \Magento\ImportExport\Model\Export\ConfigInterface $exportConfig,
        \Firebear\ImportExport\Model\Export\Dependencies\Config $configExDi
    ) {
        $this->exportConfig = $exportConfig;
        $this->diExport = $configExDi;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $entities = [];
        $options = [['label' => __('-- Please Select --'), 'value' => '']];
        foreach ($this->exportConfig->getEntities() as $entityName => $entityConfig) {
            if ($entityName == 'stock_sources') {
                continue;
            }
            $options[] = ['value' => $entityName, 'label' => __($entityConfig['label'])];
            $entities[] = $entityName;
        }
        $data = $this->diExport->get();
        foreach ($data as $typeName => $type) {
            if (in_array($typeName, $entities)) {
                continue;
            }
            $option = ['value' => $typeName, 'label' => $type['label']];

            if (isset($type['fields'])) {
                $option['fields'] = $type['fields'];
            }

            $options[] = $option;
        }

        return $options;
    }
}
