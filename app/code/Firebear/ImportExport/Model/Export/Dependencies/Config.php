<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export\Dependencies;

/**
 * Class Config
 * @package Firebear\ImportExport\Model\Source
 */
class Config extends \Magento\Framework\Config\Data implements ConfigInterface
{

    /**
     * Config constructor.
     * @param \Magento\Framework\Config\CacheInterface $cache
     * @param Config\Reader $reader
     * @param string $cacheId
     */
    public function __construct(
        \Magento\Framework\Config\CacheInterface $cache,
        \Firebear\ImportExport\Model\Export\Dependencies\Config\Reader $reader,
        $cacheId = 'firebear_importexport_export_di_config'
    ) {
        parent::__construct($reader, $cache, $cacheId);
    }

    /**
     * Get configuration of entity by name
     *
     * @param string $name
     * @return array
     * @deprecated it is not used
     */
    public function getEntity($name)
    {
        return $this->get('entity/' . $name, []);
    }

    /**
     * Get model of entity by name
     *
     * @param string $name
     * @return array
     */
    public function getModel($name)
    {
        $model = $this->get($name . '/model');
        if (!$model) {
            foreach ($this->get() as $parentName => $entity) {
                $names = array_keys($entity['fields'] ?? []);
                if (in_array($name, $names)) {
                    $model = $this->get($parentName . '/model');
                }
            }
        }
        return $model;
    }
}
