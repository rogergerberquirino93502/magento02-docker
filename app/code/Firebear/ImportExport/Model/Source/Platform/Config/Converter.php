<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Platform\Config;

use Magento\Framework\Config\ConverterInterface;

/**
 * Class Converter
 *
 * @package Firebear\ImportExport\Model\Source\Platform\Config
 */
class Converter implements ConverterInterface
{
    /**
     * Convert dom node tree to array
     *
     * @param \DOMDocument $source
     * @return array
     * @throws \InvalidArgumentException
     */
    public function convert($source)
    {
        $config = [];
        /** @var \DOMNodeList $entities */
        $platforms = $source->getElementsByTagName('platform');
        /** @var \DOMNode $entityConfig */
        foreach ($platforms as $platform) {
            $attributes = $platform->attributes;
            $name = $attributes->getNamedItem('name')->nodeValue;
            $entity = $attributes->getNamedItem('entity')->nodeValue;
            $config[$entity][$name] = [
                'label' => $attributes->getNamedItem('label')->nodeValue,
                'model' => $attributes->getNamedItem('model')
                    ? $attributes->getNamedItem('model')->nodeValue
                    : null
            ];

            $attributeCollection = $platform->getElementsByTagName('attribute');
            foreach ($attributeCollection as $attribute) {
                $attributeName = $attribute->attributes->getNamedItem('code')->nodeValue;
                $config[$entity][$name]['fields'][$attributeName] = [
                    'reference' => $attribute->attributes->getNamedItem('reference')->nodeValue,
                    'label' => $attribute->attributes->getNamedItem('label')
                        ? $attribute->attributes->getNamedItem('label')->nodeValue : '',
                    'default' => $attribute->attributes->getNamedItem('default')
                        ? $attribute->attributes->getNamedItem('default')->nodeValue : ''
                ];
            }

            $descriptionCollection = $platform->getElementsByTagName('description');
            foreach ($descriptionCollection as $description) {
                $config[$entity][$name]['descs'][] = [
                    'label' => $description->attributes->getNamedItem('label')->nodeValue
                ];
            }

            $linkCollection = $platform->getElementsByTagName('link');
            foreach ($linkCollection as $link) {
                $config[$entity][$name]['links'][] = [
                    'label' => $link->attributes->getNamedItem('label')->nodeValue,
                    'suffix' => $link->attributes->getNamedItem('suffix')
                        ? $link->attributes->getNamedItem('suffix')->nodeValue : '',
                    'entity' => $link->attributes->getNamedItem('entity')
                        ? $link->attributes->getNamedItem('entity')->nodeValue : ''
                ];
            }

            $fieldCollection = $platform->getElementsByTagName('field');
            foreach ($fieldCollection as $field) {
                $config[$entity][$name]['source_fields'][] = [
                    'id' => $field->attributes->getNamedItem('id')->nodeValue,
                    'name' => $field->attributes->getNamedItem('name')->nodeValue,
                    'label' => $field->attributes->getNamedItem('label')->nodeValue,
                    'type' => $field->attributes->getNamedItem('type')->nodeValue,
                    'required' => $field->attributes->getNamedItem('required')
                        ? $field->attributes->getNamedItem('required')->nodeValue : false,
                    'validation' => $field->attributes->getNamedItem('validation')
                        ? $field->attributes->getNamedItem('validation')->nodeValue : null,
                    'notice' => $field->attributes->getNamedItem('notice')
                        ? $field->attributes->getNamedItem('notice')->nodeValue : '',
                    'value' => $field->attributes->getNamedItem('value')
                        ? $field->attributes->getNamedItem('value')->nodeValue : '',
                    'componentType' => $field->attributes->getNamedItem('componentType')
                        ? $field->attributes->getNamedItem('componentType')->nodeValue : '',
                    'component' => $field->attributes->getNamedItem('component')
                        ? $field->attributes->getNamedItem('component')->nodeValue : '',
                    'template' => $field->attributes->getNamedItem('template')
                        ? $field->attributes->getNamedItem('template')->nodeValue : '',
                    'url' => $field->attributes->getNamedItem('url')
                        ? $field->attributes->getNamedItem('url')->nodeValue : '',
                    'options' => $field->attributes->getNamedItem('options')
                        ? $field->attributes->getNamedItem('options')->nodeValue : '',
                    'source_options' => $field->attributes->getNamedItem('source_options')
                        ? $field->attributes->getNamedItem('source_options')->nodeValue : '',
                    'formElement' => $field->attributes->getNamedItem('formElement')
                        ? $field->attributes->getNamedItem('formElement')->nodeValue : '',
                ];
            }
        }
        return $config;
    }
}
