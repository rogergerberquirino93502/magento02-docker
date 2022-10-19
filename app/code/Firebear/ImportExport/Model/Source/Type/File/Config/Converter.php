<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Type\File\Config;

use Magento\Framework\Config\ConverterInterface;
use Firebear\ImportExport\Model\Lib\LibPoolInterface;

/**
 * Config converter
 */
class Converter implements ConverterInterface
{
    /**
     * Install lib pool
     *
     * @var LibPoolInterface
     */
    protected $libPool;

    /**
     * Initialize converter
     *
     * @param LibPoolInterface $libPool
     */
    public function __construct(
        LibPoolInterface $libPool
    ) {
        $this->libPool = $libPool;
    }

    /**
     * Convert dom node tree to array
     *
     * @param \DOMDocument $source
     * @return array
     */
    public function convert($source)
    {
        $result = [];
        /** @var \DOMNode $templateNode */
        foreach ($source->documentElement->childNodes as $typeNode) {
            if ($typeNode->nodeType != XML_ELEMENT_NODE) {
                continue;
            }
            $typeName = $typeNode->attributes->getNamedItem('name')->nodeValue;
            if ($this->isAvailable($typeName)) {
                $typeLabel = $typeNode->attributes->getNamedItem('label')->nodeValue;
                $typeModel = $typeNode->attributes->getNamedItem('model')->nodeValue;
                $direction = $typeNode->attributes->getNamedItem('direction')->nodeValue;
                $result[$direction][$typeName] = [
                    'label' => $typeLabel,
                    'model' => $typeModel
                ];
            }
        }
        return $result;
    }

    /**
     * Check whether adapter is available
     *
     * @param string $extension
     * @return bool
     */
    private function isAvailable($extension)
    {
        foreach ($this->libPool->get() as $lib) {
            if (!$lib->isAvailable($extension)) {
                return false;
            }
        }
        return true;
    }
}
