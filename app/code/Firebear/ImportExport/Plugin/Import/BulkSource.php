<?php

namespace Firebear\ImportExport\Plugin\Import;

use Magento\Framework\App\CacheInterface;
use Firebear\ImportExport\Model\Cache\Type\ImportProduct as ImportProductCache;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Catalog\Api\Data\ProductInterface;

/**
 * Class BulkSourceAssignUnassign
 * @package Firebear\ImportExport\Plugin\Import
 */
class BulkSource
{
    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    protected $productEntityLinkField;

    /**
     * BulkSource constructor.
     * @param CacheInterface $cache
     * @param MetadataPool $metadataPool
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        CacheInterface $cache,
        MetadataPool $metadataPool,
        ResourceConnection $resourceConnection
    ) {
        $this->cache = $cache;
        $this->resourceConnection = $resourceConnection;
        $this->metadataPool = $metadataPool;
    }

    /**
     * @param $subj
     * @param $result
     * @param $skus
     * @return mixed
     */
    public function afterExecute($subj, $result, $skus)
    {
        if ($skus) {
            foreach ($this->getProductIds($skus) as $productId) {
                $this->cache->clean([ImportProductCache::CACHE_TAG . '_' . $productId]);
            }
        }

        return $result;
    }

    /**
     * @param $skus
     * @return array
     */
    protected function getProductIds($skus)
    {
        $conn = $this->resourceConnection->getConnection();
        $table = $conn->getTableName('catalog_product_entity');
        $query = $conn->select()->from($table, $this->getProductEntityLinkField())->where('sku IN(?)', $skus);
        return $conn->fetchCol($query);
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function getProductEntityLinkField()
    {
        if (!$this->productEntityLinkField) {
            $this->productEntityLinkField = $this->metadataPool
                ->getMetadata(ProductInterface::class)
                ->getLinkField();
        }
        return $this->productEntityLinkField;
    }
}
