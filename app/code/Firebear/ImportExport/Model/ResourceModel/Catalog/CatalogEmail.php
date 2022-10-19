<?php

namespace Firebear\ImportExport\Model\ResourceModel\Catalog;

use Magento\Framework\App\ResourceConnection;

/**
 * Class CatalogEmail
 * @package Firebear\ImportExport\Model\ResourceModel\Catalog
 */
class CatalogEmail
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
     * @param $productIds
     * @return array
     */
    public function getExistingProductIds($productIds)
    {
        $conn = $this->resourceConnection->getConnection();
        $query = $conn->select()
            ->from($this->resourceConnection->getTableName('email_catalog'), 'product_id')
            ->where('product_id IN(?)', $productIds);

        return $conn->fetchCol($query);
    }
}
