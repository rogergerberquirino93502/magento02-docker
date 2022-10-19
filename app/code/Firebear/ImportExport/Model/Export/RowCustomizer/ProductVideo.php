<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export\RowCustomizer;

use Exception;
use Firebear\ImportExport\Model\Export\Product;
use Firebear\ImportExport\Model\Import;
use Magento\CatalogImportExport\Model\Export\RowCustomizerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * Class Tax
 *
 * @package Firebear\ImportExport\Model\Export\RowCustomizer
 */
class ProductVideo implements RowCustomizerInterface
{
    const VIDEO_URL_COLUMN = 'video_url';

    const EXTERNAL_VIDEO = 'external-video';

    /**
     * @var AdapterInterface
     */
    private $connection;
    /**
     * @var ResourceConnection
     */
    private $resourceModel;

    private $videoURLData = [];
    /**
     * @var Product
     */
    private $entity;

    /**
     * ProductVideo constructor.
     * @param Product $entity
     * @param ResourceConnection $resource
     */
    public function __construct(
        Product $entity,
        ResourceConnection $resource
    ) {
        $this->connection = $resource->getConnection();
        $this->resourceModel = $resource;
        $this->entity = $entity;
    }

    /**
     * Prepare data for export
     *
     * @param mixed $collection
     * @param int[] $productIds
     * @return mixed
     */
    public function prepareData($collection, $productIds)
    {
        if (empty($this->videoURLData)) {
            $videoData = [];
            foreach ($this->getMediaGallery($productIds) as $productId => $item) {
                if (is_array($item)) {
                    foreach ($item as $mediaURL) {
                        $videoData[] = $mediaURL['_media_url'];
                    }
                }
                $this->videoURLData[$productId] = implode(
                    Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR,
                    array_unique($videoData)
                );
            }
        }
        return $this;
    }

    /**
     * @param array $productIds
     * @return array
     */
    protected function getMediaGallery(array $productIds)
    {
        if (empty($productIds)) {
            return [];
        }
        $productEntityField = $this->entity->_getProductEntityLinkField();
        $select = $this->connection->select()->from(
            ['mgvte' => $this->resourceModel->getTableName('catalog_product_entity_media_gallery_value_to_entity')],
            [
                "mgvte.$productEntityField",
                'mgvte.value_id',
            ]
        )->joinLeft(
            ['mg' => $this->resourceModel->getTableName('catalog_product_entity_media_gallery')],
            '(mg.value_id = mgvte.value_id)',
            [
                'mg.attribute_id',
                'filename' => 'mg.value',
            ]
        )->joinLeft(
            ['mgvi' => $this->resourceModel->getTableName('catalog_product_entity_media_gallery_value_video')],
            "(mg.value_id = mgvi.value_id)",
            [
                'mgvi.title',
                'mgvi.url',
                'mgvi.description',
                'mgvi.store_id',
                'mgvi.provider',
                'mgvi.metadata',
            ]
        )->where(
            "mgvte.$productEntityField IN (?)",
            $productIds
        );
        $rowMediaGallery = [];
        $stmt = $this->connection->query($select);
        try {
            while ($mediaRow = $stmt->fetch()) {
                if ($mediaRow['url'] === null) {
                    continue;
                }
                $rowMediaGallery[$mediaRow[$productEntityField]][] = [
                    '_media_url' => $mediaRow['url'],
                ];
            }
        } catch (Exception $exception) {
        }

        return $rowMediaGallery;
    }

    /**
     * Set headers columns
     *
     * @param array $columns
     * @return mixed
     */
    public function addHeaderColumns($columns)
    {
        $columns = array_merge(
            $columns,
            [self::VIDEO_URL_COLUMN]
        );
        return $columns;
    }

    /**
     * Add data for export
     *
     * @param array $dataRow
     * @param int $productId
     * @return mixed
     */
    public function addData($dataRow, $productId)
    {
        if (!empty($this->videoURLData[$productId])) {
            $dataRow[self::VIDEO_URL_COLUMN] = $this->videoURLData[$productId];
        }
        return $dataRow;
    }

    /**
     * Calculate the largest links block
     *
     * @param array $additionalRowsCount
     * @param int $productId
     * @return mixed
     */
    public function getAdditionalRowsCount($additionalRowsCount, $productId)
    {
        if (!empty($this->videoURLData[$productId])) {
            $additionalRowsCount = max($additionalRowsCount, count($this->videoURLData[$productId]));
        }
        return $additionalRowsCount;
    }
}
