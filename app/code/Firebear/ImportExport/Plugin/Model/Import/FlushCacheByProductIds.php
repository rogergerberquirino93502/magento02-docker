<?php
/**
 * Copyright © 2019 Firebear Studio GmbH. All rights reserved.
 */

namespace Firebear\ImportExport\Plugin\Model\Import;

use Magento\Framework\EntityManager\EventManager;
use Magento\Framework\Indexer\CacheContextFactory;

/**
 * Clean cache for given product ids.
 */
class FlushCacheByProductIds
{
    /**
     * @var CacheContextFactory
     */
    private $cacheContextFactory;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var string
     */
    private $productCacheTag;

    /**
     * @param CacheContextFactory $cacheContextFactory
     * @param EventManager $eventManager
     * @param string $productCacheTag
     */
    public function __construct(
        CacheContextFactory $cacheContextFactory,
        EventManager $eventManager,
        string $productCacheTag
    ) {
        $this->cacheContextFactory = $cacheContextFactory;
        $this->eventManager = $eventManager;
        $this->productCacheTag = $productCacheTag;
    }

    /**
     * Clean cache for given product ids.
     *
     * @param array $productIds
     * @return void
     */
    public function aroundExecute(
        \Magento\InventoryCache\Model\FlushCacheByProductIds $subject,
        \Closure $proceed,
        array $productIds
    ) {
        if ($productIds) {
            $cacheContext = $this->cacheContextFactory->create();
            $cacheContext->registerEntities($this->productCacheTag, $productIds);
            $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $cacheContext]);
        }
    }
}
