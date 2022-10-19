<?php
/**
 * ExportFieldset
 *
 * @copyright Copyright © 2019 Firebear Studio. All rights reserved.
 * @author    Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Form;

use Firebear\ImportExport\Model\Export\Dependencies\Config;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\ImportExport\Model\Export\ConfigInterface;

/**
 * Class ExportFieldset
 * @package Firebear\ImportExport\Ui\Component\Form
 */
class ExportFieldset extends \Magento\Ui\Component\Form\Fieldset
{
    /**
     * @var \Magento\ImportExport\Model\Export\ConfigInterface
     */
    protected $exportConfig;
    /**
     * @var \Firebear\ImportExport\Model\Export\Dependencies\Config
     */
    protected $configExDi;

    /**
     * ExportFieldset constructor.
     *
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\ImportExport\Model\Export\ConfigInterface $exportConfig
     * @param \Firebear\ImportExport\Model\Export\Dependencies\Config $configExDi
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        ConfigInterface $exportConfig,
        Config $configExDi,
        $components = [],
        array $data = []
    ) {
        parent::__construct($context, $components, $data);
        $this->exportConfig = $exportConfig;
        $this->configExDi = $configExDi;
    }

    public function prepare()
    {
        $entities = [];
        foreach ($this->exportConfig->getEntities() as $key => $entity) {
            $entities[$key] = $entity['name'];
        }
        foreach ($this->configExDi->get() as $key => $entity) {
            $entities[$key] = $key;
        }
        if ($config = $this->getDataByKey('config')) {
            if (isset($config['valuesForOptions'])) {
                $config['valuesForOptions'] = \array_merge($config['valuesForOptions'], $entities);
            } else {
                $config['valuesForOptions'] = $entities;
            }
            $this->setData('config', $config);
        }
        parent::prepare();
    }
}
