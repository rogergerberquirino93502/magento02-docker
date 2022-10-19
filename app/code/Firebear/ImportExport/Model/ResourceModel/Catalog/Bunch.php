<?php

namespace Firebear\ImportExport\Model\ResourceModel\Catalog;

use Magento\Framework\App\ResourceConnection;

/**
 * Class Bunch
 * @package Firebear\ImportExport\Model\ResourceModel\Catalog
 */
class Bunch
{
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * CatalogEmail constructor.
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param array $bunch
     * @return array
     */
    public function getProductIdsBySkuInBunch(array $bunch)
    {

        $conn = $this->resourceConnection->getConnection();
        $query = $conn->select()
            ->from($this->resourceConnection->getTableName('catalog_product_entity'), 'entity_id')
            ->where('sku IN(?)', array_unique(array_column($bunch, 'sku')));

        return $conn->fetchCol($query);
    }
}
