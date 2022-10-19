<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Job\Handler;

use Firebear\ImportExport\Api\Data\ImportInterface;
use Firebear\ImportExport\Model\Import\AdvancedPricing;
use Magento\Framework\App\CacheInterface;

/**
 * @api
 */
class ClearCacheAdvancedPricingHandler implements HandlerInterface
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
     * @param ImportInterface $job
     * @param string $file
     * @param int $status
     */
    public function execute(ImportInterface $job, $file, $status)
    {
        if ($job->getEntity() == 'advanced_pricing') {
            $this->cache->remove(AdvancedPricing::CLEARED_PRODUCTS_CACHE_ID . $job->getId());
        }
    }
}
