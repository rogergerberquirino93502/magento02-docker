<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\ResourceModel\Catalog;

use Magento\Framework\App\ResourceConnection;

/**
 * Class Sources
 * @package Firebear\ImportExport\Model\ResourceModel\Catalog
 */
class Sources
{
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * Sources constructor.
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param array $skus
     * @return array
     */
    public function getSourceItemsBySkus(array $skus): array
    {
        $conn = $this->resourceConnection->getConnection();
        $query = $conn->select()->from($conn->getTableName('inventory_source_item'))
            ->where('sku IN(?)', $skus);
        return $conn->fetchAll($query);
    }

    /**
     * @return array
     */
    public function getSourceCodes(): array
    {
        $conn = $this->resourceConnection->getConnection();
        $query = $conn->select()->from($conn->getTableName('inventory_source'), 'source_code');
        return $conn->fetchCol($query);
    }
}
