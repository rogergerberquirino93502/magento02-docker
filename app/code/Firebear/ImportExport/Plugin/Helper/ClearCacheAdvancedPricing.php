<?php

namespace Firebear\ImportExport\Plugin\Helper;

use Firebear\ImportExport\Model\Import\AdvancedPricing;
use Magento\Framework\App\CacheInterface;

class ClearCacheAdvancedPricing
{
    /**
     * @var CacheInterface
     */
    protected $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param $subject
     * @param $id
     */
    public function beforeBeforeRun($subject, $id)
    {
        $this->cache->remove(AdvancedPricing::CLEARED_PRODUCTS_CACHE_ID . $id);
    }
}
