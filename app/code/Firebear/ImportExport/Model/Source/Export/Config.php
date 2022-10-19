<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Export;

/**
 * Class Config
 * @package Firebear\ImportExport\Model\Source
 */
class Config extends \Magento\Framework\Config\Data implements \Firebear\ImportExport\Model\Source\ConfigInterface
{

    public function __construct(
        \Magento\Framework\Config\CacheInterface $cache,
        \Firebear\ImportExport\Model\Source\Export\Reader $reader,
        $cacheId = 'firebear_importexport_export_config'
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
