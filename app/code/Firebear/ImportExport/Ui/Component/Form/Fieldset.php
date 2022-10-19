<?php
/**
 * Fieldset
 *
 * @copyright Copyright © 2019 Firebear Studio. All rights reserved.
 * @author    Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Form;

use Firebear\ImportExport\Model\Source\Import\Config;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

/**
 * Class Fieldset
 * @package Firebear\ImportExport\Ui\Component\Form
 */
class Fieldset extends \Magento\Ui\Component\Form\Fieldset
{
    /**
     * @var \Firebear\ImportExport\Model\Source\Import\Config
     */
    protected $config;

    /**
     * Fieldset constructor.
     *
     * @param ContextInterface $context
     * @param \Firebear\ImportExport\Model\Source\Import\Config $config
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        Config $config,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $components, $data);
        $this->config = $config;
    }

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
