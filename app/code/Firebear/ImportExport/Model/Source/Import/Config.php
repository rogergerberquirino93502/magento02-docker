<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Import;

/**
 * Class Config
 * @package Firebear\ImportExport\Model\Source
 */
class Config extends \Magento\Framework\Config\Data implements \Magento\ImportExport\Model\Import\ConfigInterface
{
    /**
     * Config constructor.
     * @param \Magento\Framework\Config\CacheInterface $cache
     * @param \Firebear\ImportExport\Model\AbstractReader $reader
     * @param null $cacheId
     */
    public function __construct(
        \Magento\Framework\Config\CacheInterface $cache,
        \Firebear\ImportExport\Model\AbstractReader $reader,
        $cacheId = null
    ) {
        parent::__construct($reader, $cache, $cacheId);
    }

    /**
     * @return array|mixed|null
     */
    public function getEntities()
    {
        return $this->get('entities');
    }

    /**
     * Retrieve import entity types configuration
     *
     * @param string $entity
     * @return array
     */
    public function getEntityTypes($entity)
    {
        $entities = $this->getEntities();
        return isset($entities[$entity]) ? $entities[$entity]['types'] : [];
    }

    /**
     * Retrieve a list of indexes which are affected by import of the specified entity.
     *
     * @param string $entity
     * @return array
     */
    public function getRelatedIndexers($entity)
    {
        $entities = $this->getEntities();
        return isset($entities[$entity]) ? $entities[$entity]['relatedIndexers'] : [];
    }
}
