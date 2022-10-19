<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Entity;

use Firebear\ImportExport\Model\ExportFactory;
use Firebear\ImportExport\Model\ExportJob;
use Firebear\ImportExport\Ui\Component\Listing\Column\Entity\Export\Options as EntityOptions;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Registry;

/**
 * Class Options
 */
class Options implements OptionSourceInterface
{
    /**
     * @var array
     */
    public $options = [];

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var EntityOptions
     */
    private $entityOptions;

    /**
     * @var ExportFactory
     */
    private $exportFactory;

    /**
     * @var array
     */
    private $excludedEntityOptions = [
        'catalog_product' => [
            'special_from_date',
            'special_to_date'
        ]
    ];

    /**
     * Options constructor.
     * @param Registry $coreRegistry
     * @param EntityOptions $entityOptions
     * @param ExportFactory $exportFactory
     */
    public function __construct(
        Registry $coreRegistry,
        EntityOptions $entityOptions,
        ExportFactory $exportFactory
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->entityOptions = $entityOptions;
        $this->exportFactory = $exportFactory;
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            /** @var ExportJob $exportModel */
            $exportModel = $this->coreRegistry->registry('export_job');
            $options[] = [
                'value' => '',
                'label' => __('-- Please Select --')
            ];
            if ($exportModel instanceof ExportJob) {
                $entity = $exportModel->getEntity();
                if ($entity) {
                    $entityOptions = $this->_loadEntityOptions($entity);
                    $parseEntityOptions = $entityOptions[$entity] ?? [];
                    $options = array_merge($options, $parseEntityOptions);
                }
            }
            $this->options = $options;
        }
        return $this->options ?? [];
    }

    public function _loadEntityOptions($entity)
    {
        $options = [];
        foreach ($this->entityOptions->toOptionArray() as $item) {
            if (isset($item['fields'])) {
                foreach ($item['fields'] as $entityName => $field) {
                    if ($entity != $entityName) {
                        continue;
                    }
                    $options = $this->_prepareEntityOptions($item['value']);
                }
            } elseif (isset($item['value']) && $entity == $item['value']) {
                $options = $this->_prepareEntityOptions($entity);
            }
        }
        return $options;
    }

    /**
     * @param string $entity
     * @return array
     */
    protected function _prepareEntityOptions($entity)
    {
        $options = [];
        $childs = [];
        $fields = $this->exportFactory
            ->create()
            ->setData(['entity' => $entity])
            ->getFields();

        foreach ($fields as $field) {
            if (!isset($field['optgroup-name'])) {
                $excludedEntityOptions = $this->excludedEntityOptions[$entity] ?? [];
                if (!in_array($field, $excludedEntityOptions)) {
                    $childs[] = ['value' => $field, 'label' => $field];
                }
            } else {
                $options[$field['optgroup-name']] = $field['value'];
            }
        }
        if (!isset($options[$entity])) {
            $options[$entity] = $childs;
        }

        return $options;
    }
}
