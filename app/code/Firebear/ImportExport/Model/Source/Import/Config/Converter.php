<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Import\Config;

use Magento\Framework\Config\ConverterInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\App\Utility\Classes;
use DOMDocument;
use DOMNodeList;
use DOMNode;

/**
 * Class Converter
 *
 * @package Firebear\ImportExport\Model\Source\Import\Config
 */
class Converter implements ConverterInterface
{
    /**
     * @var Manager
     */
    protected $manager;

    /**
     * Converter constructor.
     * @param Manager $moduleManager
     */
    public function __construct(Manager $moduleManager)
    {
        $this->manager = $moduleManager;
    }

    /**
     * Convert dom node tree to array
     *
     * @param DOMDocument $sourceConfig
     *
     * @return array
     */
    public function convert($sourceConfig)
    {
        $output = ['entities' => []];
        /** @var DOMNodeList $entities */
        $entities = $sourceConfig->getElementsByTagName('entity');
        /** @var DOMNode $entityConfig */
        foreach ($entities as $entityConfig) {
            $attributes = $entityConfig->attributes;
            $name = $attributes->getNamedItem('name')->nodeValue;
            $label = $attributes->getNamedItem('label')->nodeValue;
            $behaviorModel = $attributes->getNamedItem('behaviorModel')->nodeValue;
            $model = $attributes->getNamedItem('model')->nodeValue;
            if (!$this->manager->isEnabled(Classes::getClassModuleName($model))) {
                continue;
            }
            $output['entities'][$name] = [
                'name' => $name,
                'label' => $label,
                'behaviorModel' => $behaviorModel,
                'model' => $model,
                'types' => [],
                'relatedIndexers' => [],
            ];
        }

        /** @var DOMNodeList $entityTypes */
        $entityTypes = $sourceConfig->getElementsByTagName('entityType');
        /** @var DOMNode $entityTypeConfig */
        foreach ($entityTypes as $entityTypeConfig) {
            $attributes = $entityTypeConfig->attributes;
            $name = $attributes->getNamedItem('name')->nodeValue;
            $model = $attributes->getNamedItem('model')->nodeValue;
            $entity = $attributes->getNamedItem('entity')->nodeValue;

            if (!$this->manager->isEnabled(Classes::getClassModuleName($model))) {
                continue;
            }

            if (isset($output['entities'][$entity])) {
                $output['entities'][$entity]['types'][$name] = ['name' => $name, 'model' => $model];
            }
        }

        /** @var DOMNodeList $relatedIndexers */
        $relatedIndexers = $sourceConfig->getElementsByTagName('relatedIndexer');
        /** @var DOMNode $relatedIndexerConfig */
        foreach ($relatedIndexers as $relatedIndexerConfig) {
            $attributes = $relatedIndexerConfig->attributes;
            $name = $attributes->getNamedItem('name')->nodeValue;
            $entity = $attributes->getNamedItem('entity')->nodeValue;

            if (isset($output['entities'][$entity])) {
                $output['entities'][$entity]['relatedIndexers'][$name] = ['name' => $name];
            }
        }
        return $output;
    }
}
