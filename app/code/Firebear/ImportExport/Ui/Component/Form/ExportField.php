<?php
/**
 * Field
 *
 * @copyright Copyright © 2019 Firebear Studio. All rights reserved.
 * @author    Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Form;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

/**
 * Class Field
 * @package Firebear\ImportExport\Ui\Component\Form
 */
class ExportField extends \Magento\Ui\Component\Form\Field
{
    /**
     * @var \Firebear\ImportExport\Model\Source\Import\Config
     */
    protected $config;

    /**
     * Field constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param \Firebear\ImportExport\Model\Source\Import\Config $config
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Firebear\ImportExport\Model\Source\Import\Config $config,
        $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->config = $config;
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepare()
    {
        $entities = [];
        foreach ($this->config->getEntities() as $key => $entity) {
            $entities[$key] = $entity['name'];
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
