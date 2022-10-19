<?php

namespace Firebear\ImportExport\Model\ResourceModel\Catalog;

use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * Class GalleryReindex
 * @package Firebear\ImportExport\Model\ResourceModel\Catalog
 */
class GalleryResize
{
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var string
     */
    private $productEntityLinkField;

    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    /**
     * GalleryResize constructor.
     * @param ResourceConnection $resourceConnection
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        MetadataPool $metadataPool
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->metadataPool = $metadataPool;
    }

    /**
     * @param $rows
     * @return array
     * @throws \Exception
     */
    public function getImages($rows)
    {
        $entityId = $this->getProductEntityLinkField();
        $conn = $this->resourceConnection->getConnection();
        $query = $conn->select()
            ->from(['cpe' => $this->resourceConnection->getTableName('catalog_product_entity')], 'cpe.sku AS sku')
            ->joinLeft(
                ['cpegve' => $this->resourceConnection->getTableName(
                    'catalog_product_entity_media_gallery_value_to_entity'
                )],
                "cpegve.{$entityId}=cpe.entity_id"
            )->join(
                ['cpeg' => $this->resourceConnection->getTableName('catalog_product_entity_media_gallery')],
                'cpeg.value_id=cpegve.value_id',
                'cpeg.value AS value'
            )
            ->where('cpe.entity_id IN(?)', array_unique(array_column($rows, 'entity_id')));

        return $conn->fetchAll($query);
    }

    /**
     * Get product entity link field.
     *
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
