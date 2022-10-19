<?php

namespace Firebear\ImportExport\Plugin\Import;

use Magento\Framework\App\CacheInterface;
use Firebear\ImportExport\Model\Cache\Type\ImportProduct as ImportProductCache;

/**
 * Class Action
 * @package Firebear\ImportExport\Plugin\Import
 */
class Action
{
    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * ClearCacheAfterDeleteProduct constructor.
     * @param CacheInterface $cache
     */
    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param $subj
     * @param $result
     * @param $productIds
     */
    public function afterUpdateAttributes($subj, $result, $productIds)
    {
        foreach ($productIds as $productId) {
            $this->cache->clean([ImportProductCache::CACHE_TAG . '_' . $productId]);
        }

        return $result;
    }
}
