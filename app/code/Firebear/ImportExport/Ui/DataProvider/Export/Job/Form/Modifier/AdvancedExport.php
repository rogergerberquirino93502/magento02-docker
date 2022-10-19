<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\DataProvider\Export\Job\Form\Modifier;

use Firebear\ImportExport\Model\Source\Export\Config;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Backend\Model\UrlInterface;

/**
 * Data provider for advanced inventory form
 */
class AdvancedExport implements ModifierInterface
{
    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var \Firebear\ImportExport\Model\Export\Dependencies\Config
     */
    protected $configExDi;

    /** @var \Magento\Framework\ObjectManagerInterface */
    protected $objectManager;

    /**
     * @var UrlInterface
     */
    protected $backendUrl;

    /**
     * AdvancedExport constructor.
     *
     * @param ArrayManager $arrayManager
     * @param Config $config
     * @param \Firebear\ImportExport\Model\Export\Dependencies\Config $configExDi
     * @param ObjectManagerInterface $objectManager
     * @param UrlInterface $backendUrl
     */
    public function __construct(
        ArrayManager $arrayManager,
        Config $config,
        \Firebear\ImportExport\Model\Export\Dependencies\Config $configExDi,
        ObjectManagerInterface $objectManager,
        UrlInterface $backendUrl
    ) {
        $this->arrayManager = $arrayManager;
        $this->config = $config;
        $this->configExDi = $configExDi;
        $this->objectManager = $objectManager;
        $this->backendUrl = $backendUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        return $this->prepareMeta($meta);
    }

    /**
     * @return array
     */
    protected function addFieldSource()
    {
        $childrenArray = [];
        $nameSource = 'export_source_';
        $generalConfig = [
            'componentType' => 'field',
            'component' => 'Firebear_ImportExport/js/form/dep-file',
            'formElement' => 'input',
            'dataType' => 'text',
            'source' => 'export',
            'valueUpdate' => 'afterkeydown'
        ];
        $types = $this->config->get();

        foreach ($types as $typeName => $type) {
            $sortOrder = 20;
            foreach ($type['fields'] as $name => $values) {
                $localConfig = [
                    'label' => $values['label'],
                    'dataScope' => $nameSource . $typeName . "_" . $name,
                    'sortOrder' => $sortOrder,
                    'valuesForOptions' => [
                        $typeName => $typeName
                    ]
                ];
                if (isset($values['required']) && $values['required'] == "true") {
                    $localConfig['validation'] = [
                        'required-entry' => true
                    ];
                }
                if (isset($values['component']) && $values['component']) {
                    $localConfig['component'] = $values['component'];
                }

                if (isset($values['componentType']) && ($values['componentType'])) {
                    $localConfig['componentType'] = $values['componentType'];
                }

                if (isset($values['template']) && ($values['template'])) {
                    $localConfig['template'] = $values['template'];
                }

                if (isset($values['url']) && $values['url']) {
                    $localConfig['uploaderConfig'] = [
                        'url' => $this->backendUrl->getUrl($values['url'])
                    ];
                }

                if (isset($values['validation'])) {
                    $localConfig['validation'][$values['validation']] = true;
                }
                if (isset($values['notice']) && $values['notice']) {
                    $localConfig['notice'] = __($values['notice']);
                }
                if (isset($values['formElement']) && $values['formElement']) {
                    $localConfig['formElement'] = $values['formElement'];
                }
                $sortOrder += 10;
                $config = array_merge($generalConfig, $localConfig);
                $childrenArray[$nameSource . $typeName . '_' . $name] = [
                    'arguments' => [
                        'data' => [
                            'config' => $config
                        ],
                    ]
                ];
                if (isset($values['options']) && $values['options']) {
                    $childrenArray[$nameSource . $typeName . '_' . $name]['arguments']['data']['options'] = $this
                        ->objectManager->create($values['options']);
                }
                if (isset($values['source_options']) && $values['source_options']) {
                    $childrenArray[$nameSource . $typeName . '_' . $name]['arguments']['data']['source_options'] = $this
                        ->objectManager->create($values['source_options']);
                }
            }
        }

        return $childrenArray;
    }

    protected function addFieldsDependencies()
    {

        $childrenArray = [];
        $nameSource = 'behavior_field_';
        $generalConfig = [
            'componentType' => 'checkboxset',
            'component' => 'Firebear_ImportExport/js/form/dep-entity-file',
            'formElement' => 'checkbox-set',
            'multiple' => 'true',
            'source' => 'export',
            'default' => 0,
            'dataScope' => $nameSource . 'deps',
            'notice' => __('Some items may not be compatible')
        ];
        $entities = $this->configExDi->get();

        foreach ($entities as $typeName => $type) {
            $sortOrder = 90;
            $options = [];
            if (isset($type['fields'])) {
                foreach ($type['fields'] as $name => $values) {
                    $localConfig = [
                        'label' => $type['label'].' '.__('Entities'),
                        'sortOrder' => $sortOrder,
                        'valuesForOptions' => [
                            $typeName => $typeName
                        ],
                        'NOTE' => 'Note'
                    ];
                    $options[] = [
                        'label' => $values['label'],
                        'value' => $name,
                        'parent' => isset($values['parent']) ? $values['parent'] : ''
                    ];
                }
                $config = array_merge($generalConfig, $localConfig);
                $config['options'] = $options;

                $childrenArray[$nameSource . $typeName] = [
                    'arguments' => [
                        'data' => [
                            'config' => $config
                        ],
                    ]
                ];
            }
        }

        return $childrenArray;
    }

    /**
     * @param array $meta
     *
     * @return array
     */
    private function prepareMeta($meta)
    {
        $meta['source'] = ['children' => $this->addFieldSource()];
        $meta['behavior'] = ['children' => $this->addFieldsDependencies()];
        return $meta;
    }
}
