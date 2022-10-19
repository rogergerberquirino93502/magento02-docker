<?php

namespace Firebear\ImportExport\Observer\Catalog;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\CacheInterface;
use Firebear\ImportExport\Model\Cache\Type\ImportProduct as ImportProductCache;
use Magento\Catalog\Api\Data\ProductInterface;

/**
 * Class ClearCacheAfterDeleteProduct
 * @package Firebear\ImportExport\Observer\Catalog
 */
class ClearCacheAfterDeleteProduct implements ObserverInterface
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
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var ProductInterface $product */
        $product = $observer->getEvent()->getProduct();
        $this->cache->clean([ImportProductCache::CACHE_TAG . '_' . $product->getId()]);
    }
}
