<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source;

/**
 * Class Config
 * @package Firebear\ImportExport\Model\Source
 */
class Config extends \Magento\Framework\Config\Data implements \Firebear\ImportExport\Model\Source\ConfigInterface
{

    /**
     * Config constructor.
     *
     * @param \Magento\Framework\Config\CacheInterface       $cache
     * @param \Magento\Framework\Config\ReaderInterface|null $reader
     * @param string                                         $cacheId
     */
    public function __construct(
        \Magento\Framework\Config\CacheInterface $cache,
        \Firebear\ImportExport\Model\Source\Config\Reader $reader,
        $cacheId = 'firebear_importexport_config'
    ) {
        parent::__construct($reader, $cache, $cacheId);
    }

    /**
     * Get system configuration of source type by name
     *
     * @param string $name
     * @return array|mixed|null
     */
    public function getType($name)
    {
        return $this->get('type/' . $name, []);
    }
}
