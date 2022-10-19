<?php
/**
 * MediaGalleryProcessor
 *
 * @copyright Copyright © 2019 Firebear Studio. All rights reserved.
 * @author    Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Product;

use Exception;
use Firebear\ImportExport\Helper\MediaHelper;
use Firebear\ImportExport\Logger\Logger;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogImportExport\Model\Import\Product;
use Magento\CatalogImportExport\Model\Import\Product\SkuProcessor;
use Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModel;
use Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\Store\Model\Store;
use function sprintf;

/**
 * Process and saves images during import.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */

class MediaVideoGallery
{
    /**
     * @var MediaHelper
     */
    protected $mediaHelper;

    /**
     * @var SkuProcessor
     */
    private $skuProcessor;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * DB connection.
     *
     * @var AdapterInterface
     */
    private $connection;

    /**
     * @var ResourceModelFactory
     */
    private $resourceFactory;

    /**
     * @var ResourceModel
     */
    private $resourceModel;

    /**
     * @var ProcessingErrorAggregatorInterface
     */
    private $errorAggregator;

    /**
     * @var string
     */
    private $productEntityLinkField;

    /**
     * @var string
     */
    private $mediaGalleryTableName;

    /**
     * @var string
     */
    private $mediaGalleryValueTableName;

    /**
     * @var string
     */
    private $mediaGalleryEntityToValueTableName;

    /**
     * @var string
     */
    private $mediaGalleryVideoTableName;

    /**
     * @var string
     */
    private $productEntityTableName;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var array
     */
    private $oldSkus;
    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var array
     */
    protected $productIdBySkuQueue = [];

    /**
     * MediaVideoGallery constructor.
     * @param SkuProcessor $skuProcessor
     * @param MetadataPool $metadataPool
     * @param ResourceConnection $resourceConnection
     * @param ResourceModelFactory $resourceModelFactory
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param Logger $logger
     * @param ProductMetadataInterface $productMetadata
     * @param MediaHelper $videoimportexportHelper
     */
    public function __construct(
        SkuProcessor $skuProcessor,
        MetadataPool $metadataPool,
        ResourceConnection $resourceConnection,
        ResourceModelFactory $resourceModelFactory,
        ProcessingErrorAggregatorInterface $errorAggregator,
        Logger $logger,
        ProductMetadataInterface $productMetadata,
        MediaHelper $videoimportexportHelper
    ) {
        $this->skuProcessor = $skuProcessor;
        $this->metadataPool = $metadataPool;
        $this->connection = $resourceConnection->getConnection();
        $this->resourceFactory = $resourceModelFactory;
        $this->errorAggregator = $errorAggregator;
        $this->mediaHelper = $videoimportexportHelper;
        $this->logger = $logger;
        $this->productMetadata = $productMetadata;
    }

    /**
     * Save product media gallery.
     *
     * @param array $mediaGalleryData
     *
     * @return void
     * @throws Exception
     */
    public function saveMediaGallery(array $mediaGalleryData)
    {
        $this->oldSkus = $this->getOldSkus();
        $this->initMediaGalleryResources();
        $imageNames = [];
        $multiInsertData = [];
        $valueToProductId = [];
        foreach ($mediaGalleryData as &$galleryData) {
            $this->updateMediaGalleryLabelsPerStore($galleryData);
        }
        $mediaGalleryDataGlobal = array_replace_recursive(...$mediaGalleryData);
        if (!empty($mediaGalleryDataGlobal)) {
            if (empty($mediaGalleryData[Store::DEFAULT_STORE_ID])) {
                $mediaGalleryData[Store::DEFAULT_STORE_ID] = $mediaGalleryDataGlobal;
            }
            foreach ($mediaGalleryDataGlobal as $productSku => $mediaGalleryRows) {
                $productId = $this->getProductId($productSku);
                $insertedGalleryImgs = [];
                $this->prepareMediaGalleryRow(
                    $mediaGalleryRows,
                    $productId,
                    $valueToProductId,
                    $imageNames,
                    $multiInsertData,
                    $insertedGalleryImgs
                );
            }

            $countRows = $this->connection->insertOnDuplicate($this->mediaGalleryTableName, $multiInsertData);
            $id = $this->connection->lastInsertId($this->mediaGalleryTableName);
            $newMediaSelect = $this->connection->select()->from($this->mediaGalleryTableName, ['value_id', 'value'])
                ->where('value_id >= ?', $id)
                ->limit($countRows);

            $newMediaValues = $this->connection->fetchAssoc($newMediaSelect);
            foreach ($mediaGalleryData as $storeId => $storeMediaGalleryData) {
                foreach ($storeMediaGalleryData as $mediaGalleryRows) {
                    foreach ($mediaGalleryRows as $insertValue) {
                        $this->processMediaPerStore((int)$storeId, $insertValue, $newMediaValues, $valueToProductId);
                    }
                }
            }
        }
    }

    /**
     * @param $mediaGalleryRows
     * @param $productId
     * @param $valueToProductId
     * @param $imageNames
     * @param $multiInsertData
     * @param $insertedGalleryImgs
     */
    private function prepareMediaGalleryRow(
        $mediaGalleryRows,
        $productId,
        &$valueToProductId,
        &$imageNames,
        &$multiInsertData,
        &$insertedGalleryImgs
    ) {
        foreach ($mediaGalleryRows as $insertValue) {
            if (!in_array($insertValue['value'], $insertedGalleryImgs)) {
                $attributeInsertValue =
                    is_array($insertValue['value']) ? $insertValue['value'] : [$insertValue['value']];
                $attributeId = $insertValue['attribute_id'];
                $mediaType = isset($insertValue['video_url']) ? 'external-video' : 'image';
                foreach ($attributeInsertValue as $attributeValue) {
                    $valueArr = [
                        'attribute_id' => $attributeId,
                        'value' => $attributeValue,
                        'media_type' => $mediaType,
                    ];
                    $valueToProductId[$attributeValue][] = $productId;
                    $imageNames[] = $attributeValue;
                    $multiInsertData[] = $valueArr;
                    $insertedGalleryImgs[] = $attributeValue;
                }
            }
        }
    }

    /**
     * @return $this
     */
    public function resetIdBySku()
    {
        $this->productIdBySkuQueue = [];
        return $this;
    }

    /**
     * Init media gallery resources.
     *
     * @return void
     */
    private function initMediaGalleryResources()
    {
        if (null == $this->mediaGalleryTableName) {
            $this->productEntityTableName = $this->getResource()->getTable('catalog_product_entity');
            $this->mediaGalleryTableName = $this->getResource()->getTable('catalog_product_entity_media_gallery');
            $this->mediaGalleryValueTableName = $this->getResource()->getTable(
                'catalog_product_entity_media_gallery_value'
            );
            $this->mediaGalleryEntityToValueTableName = $this->getResource()->getTable(
                'catalog_product_entity_media_gallery_value_to_entity'
            );
            $this->mediaGalleryVideoTableName = $this->getResource()->getTable(
                'catalog_product_entity_media_gallery_value_video'
            );
        }
    }

    /**
     * Get resource.
     *
     * @return ResourceModel
     */
    private function getResource()
    {
        if (!$this->resourceModel) {
            $this->resourceModel = $this->resourceFactory->create();
        }

        return $this->resourceModel;
    }

    /**
     * @param $data
     * @return array
     * @throws Exception
     */
    public function initDataQueue($data)
    {
        if ($this->productIdBySkuQueue) {
            return $this->productIdBySkuQueue;
        }

        $this->productIdBySkuQueue = array_column($this->connection->fetchAssoc(
            $this->connection->select()
                ->from(
                    $this->connection->getTableName('catalog_product_entity'),
                    ['sku', $this->getProductEntityLinkField()]
                )->where('sku IN (?)', array_unique(array_column($data, 'sku')))
        ), $this->getProductEntityLinkField(), 'sku');

        return $this->productIdBySkuQueue;
    }

    /**
     * @param $sku
     * @return mixed|null
     */
    public function getIdBySku($sku)
    {
        return $this->productIdBySkuQueue[$sku] ?? null;
    }

    /**
     * @param $productSku
     * @return string
     * @throws Exception
     */
    protected function getProductId($productSku)
    {
        if ($this->productIdBySkuQueue) {
            return $this->productIdBySkuQueue[$productSku] ?? null;
        }

        if (version_compare($this->productMetadata->getVersion(), '2.2.0', '>=')) {
            $productSku = mb_strtolower($productSku);
        }
        $productId = $this->skuProcessor->getNewSku($productSku)[$this->getProductEntityLinkField()] ?? '';
        if (!$productId && isset($this->getOldSkus()[$productSku][$this->getProductEntityLinkField()])) {
            $productId = $this->getOldSkus()[$productSku][$this->getProductEntityLinkField()] ?? '';
        }
        return $productId;
    }

    /**
     * Get product entity link field.
     *
     * @return string
     * @throws Exception
     */
    private function getProductEntityLinkField()
    {
        if (!$this->productEntityLinkField) {
            $this->productEntityLinkField = $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
        }

        return $this->productEntityLinkField;
    }

    /**
     * Save media gallery data per store.
     *
     * @param int $storeId
     * @param $insertValue
     * @param array $newMediaValues
     * @param array $valueToProductId
     *
     * @return bool
     * @throws Exception
     */
    private function processMediaPerStore(
        int $storeId,
        $insertValue,
        array &$newMediaValues,
        array &$valueToProductId
    ) {
        $multiInsertData = [];
        $dataForSkinnyTable = [];
        $dataForVideoTable = [];
        if (empty($insertValue['value'])) {
            return false;
        }
        $insertValue['value'] = is_array($insertValue['value']) ? $insertValue['value'] : [$insertValue['value']];
        foreach ($insertValue['value'] as $imageValue) {
            foreach ($newMediaValues as $value_id => $values) {
                if ($values['value'] == $imageValue) {
                    $insertValue['value_id'] = $value_id;
                    $insertValue[$this->getProductEntityLinkField()] =
                        array_shift($valueToProductId[$values['value']]);
                    unset($newMediaValues[$value_id]);
                    break;
                }
            }
            if (isset($insertValue['value_id'])) {
                $valueArr = [
                    'value_id' => $insertValue['value_id'],
                    'store_id' => $storeId,
                    $this->getProductEntityLinkField() => $insertValue[$this->getProductEntityLinkField()],
                    'label' => $insertValue['label'],
                    'position' => $insertValue['position'],
                    'disabled' => $insertValue['disabled'],
                ];
                $multiInsertData[] = $valueArr;
                $dataForSkinnyTable[] = [
                    'value_id' => $insertValue['value_id'],
                    $this->getProductEntityLinkField() => $insertValue[$this->getProductEntityLinkField()],
                ];
                if (isset($insertValue['video_url'])) {
                    $videoDetails = [];
                    try {
                        $videoDetails = $this->mediaHelper->getVideoDetails($insertValue['video_url']);
                    } catch (Exception $exception) {
                        $this->logger->critical($exception->getMessage());
                    }
                    $valueArr = [
                        'value_id' => $insertValue['value_id'],
                        'store_id' => $storeId,
                        'title' => $videoDetails['title'] ?? __('Error Fetching Video Title'),
                        'description' => $videoDetails['description'] ?? __('Error Fetching Video Description'),
                        'url' => $insertValue['video_url']
                    ];
                    $dataForVideoTable[] = $valueArr;
                }
            }
        }

        try {
            $this->connection->insertOnDuplicate(
                $this->mediaGalleryValueTableName,
                $multiInsertData,
                ['value_id', 'store_id', $this->getProductEntityLinkField(), 'label', 'position', 'disabled']
            );

            if (!empty($dataForSkinnyTable)) {
                $this->connection->insertOnDuplicate(
                    $this->mediaGalleryEntityToValueTableName,
                    $dataForSkinnyTable,
                    ['value_id']
                );
            }

            if (!empty($dataForVideoTable)) {
                $this->connection->insertOnDuplicate(
                    $this->mediaGalleryVideoTableName,
                    $dataForVideoTable,
                    ['value_id']
                );
            }
        } catch (Exception $e) {
            $this->connection->delete(
                $this->mediaGalleryTableName,
                $this->connection->quoteInto('value_id IN (?)', $newMediaValues)
            );
        }
        return true;
    }

    /**
     * Update media gallery labels.
     *
     * @param array $labels
     *
     * @return void
     * @throws Exception
     */
    public function updateMediaGalleryLabels(array $labels)
    {
        $this->updateMediaGalleryField($labels, 'label');
    }

    /**
     * Update the media gallery labels for each store
     *
     * @param $mediaGalleryData
     * @param $storeId
     */
    private function updateMediaGalleryLabelsPerStore(&$mediaGalleryData)
    {
        foreach ($mediaGalleryData as $sku => $galleryData) {
            foreach ($galleryData as $key => $labelData) {
                if (!empty($labelData['value_id']) && !empty($labelData['label'])) {
                    $this->processUpdateLabelPerStore($labelData);
                    /* Clear the data, which are used only to update the labels */
                    unset($mediaGalleryData[$sku][$key]);
                    if (!count($mediaGalleryData[$sku])) {
                        unset($mediaGalleryData[$sku]);
                    }
                }
            }
        }
    }

    /**
     * Request to update the media gallery labels for each store
     *
     * @param $valueId
     * @param $label
     * @param $storeId
     */
    protected function processUpdateLabelPerStore($labelData)
    {
        $select = $this->connection->select()
            ->from($this->mediaGalleryValueTableName, 'record_id')
            ->where('value_id = ?', $labelData['value_id'])
            ->where('store_id = ?', $labelData['store_id']);
        $recordId = $this->connection->fetchOne($select);
        if ($recordId) {
            $this->connection->update(
                $this->mediaGalleryValueTableName,
                [
                    'label' => $labelData['label'],
                ],
                [
                    'record_id = ?' => $recordId,
                    'store_id = ?' => $labelData['store_id']
                ]
            );
        } elseif ($labelData['create_new_label']) {
            unset($labelData['create_new_label']);
            $this->connection->insertOnDuplicate(
                $this->mediaGalleryValueTableName,
                $labelData
            );
        }
    }

    /**
     * Update value for requested field in media gallery entities
     *
     * @param array $data
     * @param string $field
     *
     * @return void
     * @throws Exception
     */
    private function updateMediaGalleryField(array $data, $field)
    {
        $insertData = [];
        foreach ($data as $datum) {
            $imageData = $datum['imageData'];

            if ($imageData[$field] === null) {
                $insertData[] = [
                    $field => $datum[$field],
                    $this->getProductEntityLinkField() => $imageData[$this->getProductEntityLinkField()],
                    'value_id' => $imageData['value_id'],
                    'store_id' => Store::DEFAULT_STORE_ID,
                ];
            } else {
                $this->connection->update(
                    $this->mediaGalleryValueTableName,
                    [
                        $field => $datum[$field],
                    ],
                    [
                        $this->getProductEntityLinkField() . ' = ?' => $imageData[$this->getProductEntityLinkField()],
                        'value_id = ?' => $imageData['value_id'],
                        'store_id = ?' => Store::DEFAULT_STORE_ID,
                    ]
                );
            }
        }

        if (!empty($insertData)) {
            $this->connection->insertMultiple(
                $this->mediaGalleryValueTableName,
                $insertData
            );
        }
    }

    /**
     * Update 'disabled' field for media gallery entity
     *
     * @param array $images
     *
     * @return void
     * @throws Exception
     */
    public function updateMediaGalleryVisibility(array $images)
    {
        $this->updateMediaGalleryField($images, 'disabled');
    }

    /**
     * Get existing images for current bunch.
     *
     * @param array $bunch
     *
     * @return array
     * @throws Exception
     */
    public function getExistingImages(array $bunch)
    {
        $result = [];
        if ($this->errorAggregator->hasToBeTerminated()) {
            return $result;
        }
        $this->initMediaGalleryResources();
        $productSKUs = array_map(
            'strval',
            array_column($bunch, Product::COL_SKU)
        );
        $select = $this->connection->select()->from(
            ['mg' => $this->mediaGalleryTableName],
            ['value' => 'mg.value']
        )->joinInner(
            ['mgvte' => $this->mediaGalleryEntityToValueTableName],
            '(mg.value_id = mgvte.value_id)',
            [
                $this->getProductEntityLinkField() => 'mgvte.' . $this->getProductEntityLinkField(),
                'value_id' => 'mgvte.value_id',
            ]
        )->joinLeft(
            ['mgv' => $this->mediaGalleryValueTableName],
            sprintf(
                '(mgv.%s = mgvte.%s AND mg.value_id = mgv.value_id)',
                $this->getProductEntityLinkField(),
                $this->getProductEntityLinkField()
            ),
            [
                'label' => 'mgv.label',
                'disabled' => 'mgv.disabled',
                'position' => 'mgv.position'
            ]
        )->joinLeft(
            ['mgvv' => $this->mediaGalleryVideoTableName],
            sprintf(
                '(mg.value_id = mgvv.value_id AND mgv.store_id = %d)',
                Store::DEFAULT_STORE_ID
            ),
            [
                'title' => 'mgvv.title',
                'url' => 'mgvv.url',
                'description' => 'mgvv.description'
            ]
        )->joinInner(
            ['pe' => $this->productEntityTableName],
            "(mgvte.{$this->getProductEntityLinkField()} = pe.{$this->getProductEntityLinkField()})",
            ['sku' => 'pe.sku']
        )->where(
            'pe.sku IN (?)',
            $productSKUs
        );

        foreach ($this->connection->fetchAll($select) as $image) {
            $result[$image['sku']][$image['value']] = $image;
        }
        return $result;
    }

    /**
     * @return array
     */
    private function getOldSkus()
    {
        if (!$this->oldSkus) {
            $this->oldSkus = $this->skuProcessor->getOldSkus();
        }
        return $this->oldSkus;
    }
}
