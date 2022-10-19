<?php

namespace Firebear\ImportExport\Model\Import\Product;

use Firebear\ImportExport\Model\Export\RowCustomizer\ProductVideo;
use Firebear\ImportExport\Model\Import\UploaderFactory;
use Firebear\ImportExport\Model\QueueMessage\ImagePublisher;
use Firebear\ImportExport\Traits\General;
use Firebear\ImportExport\Api\Data\SeparatorFormatterInterface;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Backend\Sku;
use Magento\Catalog\Model\Product\Media\ConfigInterface as MediaConfig;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\CatalogImportExport\Model\Import\Product;
use Magento\CatalogImportExport\Model\Import\Product\ImageTypeProcessor;
use Magento\CatalogImportExport\Model\Import\Product\MediaGalleryProcessor;
use Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory;
use Magento\MediaStorage\Service\ImageResize;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Magento\Store\Model\Store;

/**
 * Class ImageProcessor
 * @package Firebear\ImportExport\Model\Import\Product
 */
class ImageProcessor
{
    use General;

    protected $mediaGalleryProcessor;

    protected $mediaGalleryAttributeId;

    protected $resourceModelFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var ImagePublisher
     */
    protected $imagePublisher;

    /**
     * @var array
     */
    protected $rows = [];

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    protected $mediaGalleryTableName;

    protected $resource;

    protected $imagesArrayKeys = ['image', 'small_image', 'thumbnail', 'swatch_image', '_media_image', 'video_url'];

    protected $mediaImagesAttributes = [];

    protected $fileUploader;

    protected $uploaderFactory;

    /**
     * @var MediaConfig
     */
    protected $mediaConfig;

    protected $imageConfigField = ['image', 'small_image', 'thumbnail'];

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var array
     */
    protected $attrIds = [];

    protected $attrTypeId;

    /**
     * @var ProcessingErrorAggregatorInterface
     */
    protected $errorAggregator;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $mediaDirectory;

    /**
     * @var ImageTypeProcessor
     */
    protected $imageTypeProcessor;

    protected $productEntityTableName;

    protected $mediaGalleryValueTableName;

    protected $mediaGalleryEntityToValueTableName;

    protected $productEntityLinkField;

    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    /**
     * @var SeparatorFormatterInterface
     */
    private $separatorFormatter;
    /**
     * @var AttributeCollectionFactory
     */
    protected $attributeCollectionFactory;

    /**
     * @var mixed
     */
    protected $imageResizeProcessor;

    /**
     * @var array
     */
    protected $imageResizeIgnoreList = [];

    /**
     * @var array
     */
    protected $bunchUploadedImages = [];

    /**
     * ImageProcessor constructor.
     * @param Filesystem $filesystem
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     * @param SerializerInterface $serializer
     * @param UploaderFactory $uploaderFactory
     * @param ResourceModelFactory $resourceModelFactory
     * @param ImagePublisher $imagePublisher
     * @param ProductMetadataInterface $productMetadata
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param MediaConfig $mediaConfig
     * @param MetadataPool $metadataPool
     * @param ConsoleOutput $output
     * @param SeparatorFormatterInterface $separatorFormatter
     * @param AttributeCollectionFactory $attributeCollectionFactory
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        Filesystem $filesystem,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        SerializerInterface $serializer,
        UploaderFactory $uploaderFactory,
        ResourceModelFactory $resourceModelFactory,
        ImagePublisher $imagePublisher,
        ProductMetadataInterface $productMetadata,
        ProcessingErrorAggregatorInterface $errorAggregator,
        MediaConfig $mediaConfig,
        MetadataPool $metadataPool,
        ConsoleOutput $output,
        SeparatorFormatterInterface $separatorFormatter,
        AttributeCollectionFactory $attributeCollectionFactory
    ) {
        $this->storeManager = $storeManager;
        $this->serializer = $serializer;
        $this->productMetadata = $productMetadata;
        $this->imagePublisher = $imagePublisher;
        $this->resourceModelFactory = $resourceModelFactory;
        $this->uploaderFactory = $uploaderFactory;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::ROOT);
        $this->output = $output;
        $this->_logger = $logger;
        $this->errorAggregator = $errorAggregator;
        $this->mediaConfig = $mediaConfig;
        $this->metadataPool = $metadataPool;
        $this->separatorFormatter = $separatorFormatter;
        if (class_exists(ImageTypeProcessor::class)) {
            $this->imageTypeProcessor = ObjectManager::getInstance()
                ->get(ImageTypeProcessor::class);
        }
        if (class_exists(MediaGalleryProcessor::class)) {
            $this->mediaGalleryProcessor = ObjectManager::getInstance()
                ->get(MediaVideoGallery::class);
        }
        if (class_exists(ImageResize::class)) {
            $this->imageResizeProcessor = ObjectManager::getInstance()
                ->get(ImageResize::class);
        }
        $this->attributeCollectionFactory = $attributeCollectionFactory;
    }

    /**
     * @param $rowData
     * @param $mediaGallery
     * @param $existingImages
     * @param $uploadedImages
     * @param $rowNum
     * @param $existingAttributeImages
     * @throws LocalizedException
     */
    public function processMediaGalleryRows(
        &$rowData,
        &$mediaGallery,
        &$existingImages,
        &$uploadedImages,
        $rowNum,
        $existingAttributeImages
    ) {
        $disabledImages = [];
        $rowSku = $this->getCorrectSkuAsPerLength($rowData);
        if (!empty($this->config['remove_images']) && array_key_exists($rowSku, $existingImages)
            && empty($rowData[Product::COL_STORE])) {
            $this->removeExistingImages($existingImages[$rowSku], $existingAttributeImages);
            $this->clearPartUploadedImages($uploadedImages, $existingImages[$rowSku]);
            unset($existingImages[$rowSku]);
        }
        if ($this->config['remove_images'] & !empty($rowData[Product::COL_STORE])
            && !empty($existingAttributeImages[$rowData[Product::COL_SKU]]) && !empty($existingImages[$rowSku])) {
            $removeImages = [];
            foreach ($existingImages[$rowSku] as $keyImage => $image) {
                if (empty($existingAttributeImages[$rowData[Product::COL_SKU]][$keyImage])) {
                    $removeImages[$keyImage] = $image;
                    continue;
                }
                $stores = array_flip(array_keys($existingAttributeImages[$rowData[Product::COL_SKU]][$keyImage]));
                unset($stores[$rowData[Product::COL_STORE]]);
                unset($stores['admin']);
                if (!$stores) {
                    $removeImages[$keyImage] = $image;
                }
            }
            if ($removeImages) {
                $this->removeExistingImages($removeImages, $existingAttributeImages);
                $this->clearPartUploadedImages($uploadedImages, $removeImages);
                foreach ($removeImages as $key => $removeImage) {
                    unset($existingImages[$rowSku][$key]);
                }
            }
        }
        $nonExistentImages = [];
        if (empty($this->config['remove_images'])) {
            if (array_key_exists($rowSku, $existingImages)) {
                foreach ($existingImages[$rowSku] as $keyImage => $image) {
                    if (is_array($image) && !empty($image['value'])) {
                        $mediaFile = $image['value'];
                    } else {
                        $mediaFile = $keyImage;
                    }
                    $filePath = $this->mediaDirectory->getAbsolutePath(
                        '/pub/' .
                        DirectoryList::MEDIA .
                        DIRECTORY_SEPARATOR .
                        $this->mediaConfig->getMediaPath($mediaFile)
                    );
                    if (!file_exists($filePath)) {
                        $nonExistentImages[$keyImage] = $image;
                        unset($existingImages[$rowSku][$keyImage]);
                    }
                }
            }
            if (!empty($nonExistentImages)) {
                $this->removeExistingImages($nonExistentImages, $existingAttributeImages);
                $this->clearPartUploadedImages($uploadedImages, $nonExistentImages);
            }
        }
        $rowData = $this->checkAdditionalImages($rowData);
        if (isset($this->config['source_type']) && $this->config['source_type'] === 'rest') {
            unset($rowData['additional_images']);
        }

        if (isset($rowData['image']) && !empty($this->config['copy_base_image'])) {
            if (!isset($rowData['thumbnail'])) {
                $rowData['thumbnail'] = $rowData['image'];
            }
            if (!isset($rowData['small_image'])) {
                $rowData['small_image'] = $rowData['image'];
            }
        }

        list($rowImages, $rowLabels) = $this->getImagesFromRow($rowData);

        if (isset($rowData['_media_is_disabled'])) {
            $disabledImages = array_flip(
                explode($this->getMultipleValueSeparator(), $rowData['_media_is_disabled'])
            );
        }

        $rowData[Product::COL_MEDIA_IMAGE] = [];
        $imagePosition = 0;
        if (isset($existingImages[$rowSku])) {
            $existingProductImages = $existingImages[$rowSku];
            $existingImagesPosition = array_map(
                function ($value) {
                    if (is_array($value)) {
                        return (int) $value['position'] ?? 0;
                    } else {
                        return 0;
                    }
                },
                $existingProductImages
            );
            if ($existingImagesPosition) {
                $imagePosition = max($existingImagesPosition);
            }
        }
        foreach ($rowImages as $column => $columnImages) {
            foreach ($columnImages as $position => $columnImage) {
                list($isAlreadyUploaded, $alreadyUploadedFile) = $this
                    ->checkAlreadyUploadedImages($existingImages, $columnImage, $rowSku);

                if (isset($uploadedImages[$columnImage])) {
                    $uploadedFile = $uploadedImages[$columnImage];
                } elseif ($isAlreadyUploaded) {
                    $uploadedFile = $alreadyUploadedFile;
                } else {
                    $uploadedFile = $this->uploadMediaFiles(trim($columnImage), true);

                    if ($uploadedFile) {
                        $uploadedImages[$columnImage] = $uploadedFile;
                    } else {
                        $this->addRowError(
                            sprintf(
                                __('Wrong URL/path used for attribute %s For SKU : "'.$rowData['sku'].'" in rows'),
                                $column
                            ),
                            $rowData['rowNum'] ?? $rowNum,
                            null,
                            null,
                            ProcessingError::ERROR_LEVEL_WARNING
                        );
                        $rowData[$column] = null;
                    }
                }

                if ($uploadedFile && $column !== Product::COL_MEDIA_IMAGE) {
                    $rowData[$column] = $uploadedFile;
                }

                $imageNotAssigned = !isset($existingImages[$rowSku][$uploadedFile]);
                $this->bunchUploadedImages[$columnImage] = $uploadedFile;

                if ($uploadedFile && $imageNotAssigned) {
                    if ($column == Product::COL_MEDIA_IMAGE) {
                        $rowData[$column][] = $uploadedFile;
                    }
                    $pos = ++$imagePosition;
                    $mediaData = [
                        'attribute_id' => $this->getMediaGalleryAttributeId(),
                        'label' => $rowLabels[$column][$position] ?? '',
                        'position' => $pos,
                        'disabled' => isset($disabledImages[$columnImage]) ? '1' : '0',
                        'value' => $uploadedFile,
                    ];

                    if ($this->getUploader()->checkValidUrl($columnImage)) {
                        $mediaData[ProductVideo::VIDEO_URL_COLUMN] = $columnImage;
                    }

                    $this->prepareMediaGallery($mediaData, $mediaGallery, $rowData);
                    $existingImages[$rowSku][$uploadedFile] = true;
                } elseif (!empty($existingImages[$rowSku][$uploadedFile]['value_id'])) {
                    $storeViewCode = $rowData[Product::COL_STORE_VIEW_CODE] ?? 'admin';
                    $storeId = $this->getStoreIdByCode($storeViewCode) ?? 0;
                    $uploadedFileData = $existingImages[$rowSku][$uploadedFile];
                    $newImageLabel = $rowLabels[$column][$position] ?? '';
                    $createNewLabel = empty($existingImages[$rowSku][$uploadedFile]['create_new_label']);

                    if ($newImageLabel) {
                        $mediaData = [
                            'label' => $newImageLabel,
                            'position' => $uploadedFileData['position'] ?? $imagePosition,
                            'disabled' => isset($disabledImages[$columnImage]) ? '1' : '0',
                            'value_id' => $uploadedFileData['value_id'],
                            'store_id' => $storeId,
                            'create_new_label' => $createNewLabel,
                            $this->getProductEntityLinkField() => $uploadedFileData[$this->getProductEntityLinkField()]
                        ];
                        $this->prepareMediaGallery($mediaData, $mediaGallery, $rowData);
                        $existingImages[$rowSku][$uploadedFile]['create_new_label'] = true;
                    }
                }
            }
        }
    }

    /**
     * Preparing media gallery data
     *
     * @param $mediaData
     * @param $mediaGallery
     * @param $rowData
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function prepareMediaGallery($mediaData, &$mediaGallery, $rowData)
    {
        $rowSku = $rowData['sku'];
        if (version_compare($this->productMetadata->getVersion(), '2.2.4', '>=')) {
            if (empty($rowData[Product::COL_STORE_VIEW_CODE])) {
                $mediaGallery[Store::DEFAULT_STORE_ID][$rowSku][] = $mediaData;
            } else {
                $storeId = $this->getStoreIdByCode($rowData[Product::COL_STORE_VIEW_CODE]);
                $mediaGallery[$storeId][$rowSku][] = $mediaData;
            }
        } else {
            $mediaGallery[$rowSku][] = $mediaData;
        }
    }

    /**
     * @param $uploadedImages
     * @param $images
     * @return $this
     */
    public function clearPartUploadedImages(&$uploadedImages, $images)
    {
        foreach (array_flip($uploadedImages) as $path => $image) {
            if (!empty($images[$path])) {
                unset($uploadedImages[$image]);
            }
        }
        return $this;
    }

    /**
     * @param $conf
     * @return $this
     */
    public function setConfig($conf)
    {
        $this->config = $conf;
        return $this;
    }

    /**
     * @return mixed|string
     */
    public function getMultipleValueSeparator()
    {
        $separator = Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR;
        if (!empty($this->config[Import::FIELD_FIELD_MULTIPLE_VALUE_SEPARATOR])) {
            $separator = $this->config[Import::FIELD_FIELD_MULTIPLE_VALUE_SEPARATOR];
        }
        return $this->separatorFormatter->format($separator);
    }

    /**
     * @param $message
     * @throws LocalizedException
     */
    public function processImportImages($message)
    {
        $message = $this->serializer->unserialize($message);
        $this->config = $message['config'];
        $rows = $message['data'];
        $mediaGallery = $uploadedImages = [];
        $existingImages = $this->mediaGalleryProcessor->getExistingImages($rows);
        $existingAttributeImages = $this->getExistingAttributeImages($rows);

        foreach ($rows as $rowNum => &$rowData) {
            $this->processMediaGalleryRows(
                $rowData,
                $mediaGallery,
                $existingImages,
                $uploadedImages,
                $rowNum,
                $existingAttributeImages
            );
        }

        $this->saveMediaGallery($mediaGallery);
        if (!empty($this->config['deferred_images'])) {
            $this->saveImageConfig($rows);
        }
        if (!empty($this->config['image_resize'])) {
            $this->addLogWriteln(__('Start resizing images for the bunch'), $this->getOutput(), 'info');
            $this->processImageResize();
            $this->addLogWriteln(__('Resizing images for the bunch is complete'), $this->getOutput(), 'info');
        }
        $this->mediaGalleryProcessor->resetIdBySku();
    }

    /**
     * @return void
     */
    public function processImageResize()
    {
        if (!$this->imageResizeProcessor) {
            return;
        }
        try {
            if (!empty($this->bunchUploadedImages)) {
                foreach ($this->bunchUploadedImages as $path) {
                    if ($path && !in_array($path, $this->imageResizeIgnoreList)) {
                        $this->imageResizeProcessor->resizeFromImageName($path);
                        $this->imageResizeIgnoreList[] = $path;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->addLogWriteln($e->getMessage(), $this->getOutput(), 'error');
        }
    }

    /**
     * @param $rows
     * @throws \Exception
     */
    protected function saveImageConfig($rows)
    {
        $insertBunch = [];
        $this->mediaGalleryProcessor->initDataQueue($rows);
        foreach ($this->getStoreIds() as $storeId) {
            foreach ($rows as $row) {
                foreach ($this->imageConfigField as $field) {
                    if (isset($row[$field])) {
                        $insertBunch[] = [
                            'attribute_id' => $this->getAttributeIdByCode($field),
                            $this->getProductEntityLinkField() => $this->mediaGalleryProcessor->getIdBySku($row['sku']),
                            'value' => $row[$field],
                            'store_id' => $storeId
                        ];
                    }
                }
            }
        }

        if ($insertBunch) {
            $conn = $this->getResource()->getConnection();
            $conn->insertOnDuplicate(
                $this->getResource()->getTable('catalog_product_entity_varchar'),
                $insertBunch
            );
        }
    }

    /**
     * @param $code
     * @return mixed
     */
    protected function getAttributeIdByCode($code)
    {
        if (isset($this->attrIds[$code])) {
            return $this->attrIds[$code];
        }

        $connection = $this->getResource()->getConnection();
        $query = $connection->select()
            ->from($this->getResource()->getTable('eav_attribute'), 'attribute_id')
            ->where(
                "attribute_code = '{$code}' AND entity_type_id = ?",
                $this->getAttributeTypeCode()
            );

        $this->attrIds[$code] = $connection->fetchOne($query);
        return $this->attrIds[$code];
    }

    /**
     * @return string
     */
    protected function getAttributeTypeCode()
    {
        if ($this->attrTypeId) {
            return $this->attrTypeId;
        }

        $connection = $this->getResource()->getConnection();
        $query = $connection->select()
            ->from(['st' => $this->getResource()->getTable('eav_entity_type')])
            ->where('st.entity_type_code = ?', ProductAttributeInterface::ENTITY_TYPE_CODE);
        $this->attrTypeId = $connection->fetchOne($query);

        return $this->attrTypeId;
    }

    /**
     * @param array $mediaGalleryData
     * @return $this
     */
    protected function saveMediaGallery(array $mediaGalleryData)
    {
        if (empty($mediaGalleryData)) {
            return $this;
        }

        if (!empty($this->config['deferred_images'])) {
            $this->mediaGalleryProcessor->initDataQueue($mediaGalleryData);
        }

        $this->mediaGalleryProcessor->saveMediaGallery($mediaGalleryData);

        return $this;
    }

    /**
     * @return array
     */
    protected function getStoreIds()
    {
        $storeIds = array_merge(
            array_keys($this->storeManager->getStores()),
            [0]
        );
        return $storeIds;
    }

    /**
     * Get Store Id By Store Code
     *
     * @param $storeViewCode
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getStoreIdByCode($storeViewCode)
    {
        $storeByCode = $this->storeManager->getStore($storeViewCode);
        return $storeByCode->getId();
    }

    /**
     * @param array $newMediaValues
     * @param array $attributeImages
     * @return $this
     */
    public function removeExistingImages(array $newMediaValues, array $attributeImages = []): self
    {
        try {
            $this->initMediaGalleryResources();

            $connection = $this->getResource()->getConnection();
            $connection->delete(
                $this->mediaGalleryTableName,
                $connection->quoteInto('value_id IN (?)', array_column($newMediaValues, 'value_id'))
            );

            if ($attributeImages) {
                $valueIds = [];
                foreach ($newMediaValues as $newMediaValue) {
                    if (!empty($attributeImages[$newMediaValue['sku']][$newMediaValue['value']])) {
                        foreach ($attributeImages[$newMediaValue['sku']][$newMediaValue['value']] as $items) {
                            $valueIds = array_merge($valueIds, array_column($items, 'value_id'));
                        }
                    }
                }
                if ($valueIds) {
                    $connection->delete(
                        $connection->getTableName('catalog_product_entity_varchar'),
                        $connection->quoteInto('value_id IN (?)', $valueIds)
                    );
                }
            }

            if (!empty($this->config['remove_images_dir'])) {
                $table = $connection->getTableName('catalog_product_entity_varchar');
                $existValues = $connection->fetchCol(
                    $connection->select()->from($table, ['value'])
                        ->where('value IN (?)', array_column($newMediaValues, 'value'))
                );

                foreach ($newMediaValues as $newMediaValue) {
                    if (in_array($newMediaValue['value'], $existValues)) {
                        continue;
                    }
                    $mediaPath = DirectoryList::PUB . DIRECTORY_SEPARATOR . DirectoryList::MEDIA .
                        DIRECTORY_SEPARATOR . $this->mediaConfig->getMediaPath($newMediaValue['value']);
                    $productImage = $this->mediaDirectory
                        ->getAbsolutePath($mediaPath);
                    if ($this->mediaDirectory->isExist($productImage)) {
                        $this->addLogWriteln(
                            __('Remove Image for Product %1 from media directory', $newMediaValue[Product::COL_SKU]),
                            $this->getOutput(),
                            'info'
                        );
                        $this->mediaDirectory->delete($productImage);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
        }

        return $this;
    }

    /**
     * Init media gallery resources.
     *
     * @return void
     */
    public function initMediaGalleryResources()
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
        }
    }

    /**
     * @return \Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModel
     */
    protected function getResource()
    {
        if (!$this->resource) {
            $this->resource = $this->resourceModelFactory->create();
        }
        return $this->resource;
    }

    /**
     * Divide additionalImages for old Magento version
     * @param $rowData
     *
     * @return mixed
     */
    public function checkAdditionalImages($rowData)
    {
        if (version_compare($this->productMetadata->getVersion(), '2.1.11', '<')) {
            $newImage = [];
            if (isset($rowData['additional_images'])) {
                $importImages = explode($this->getMultipleValueSeparator(), $rowData['additional_images']);
                $newImage = $importImages;
            }
            if (!empty($newImage)) {
                $rowData['additional_images'] = implode(',', $newImage);
            }
        }
        return $rowData;
    }

    /**
     * @param array $rowData
     * @return array
     */
    public function getImagesFromRow(array $rowData)
    {
        $images = [];
        $labels = [];
        foreach ($this->getMediaImagesAttributes() as $column) {
            if (!empty($rowData[$column])) {
                $images[$column] = array_unique(
                    array_map(
                        'trim',
                        explode($this->getMultipleValueSeparator(), $rowData[$column])
                    )
                );

                if (!empty($rowData[$column . '_label'])) {
                    $labels[$column] = $this->parseMultipleValues($rowData[$column . '_label']);

                    if (count($labels[$column]) > count($images[$column])) {
                        $labels[$column] = array_slice($labels[$column], 0, count($images[$column]));
                    }
                }
            }
        }

        return [$images, $labels];
    }

    /**
     * @return array
     */
    public function getMediaImagesAttributes()
    {
        if (empty($this->mediaImagesAttributes)) {
            $attributeCollection = $this->attributeCollectionFactory->create();
            $attributeCollection->addFieldToSelect('attribute_code');
            $attributeCollection->addFieldToFilter('frontend_input', 'media_image');

            $attributesCode = $attributeCollection->getColumnValues('attribute_code');
            $this->mediaImagesAttributes = array_unique(array_merge($attributesCode, $this->imagesArrayKeys));
        }
        return $this->mediaImagesAttributes;
    }

    /**
     * Parse values from multiple attributes fields
     *
     * @param string $labelRow
     * @return array
     */
    private function parseMultipleValues($labelRow)
    {
        return $this->parseMultiselectValues(
            $labelRow,
            $this->getMultipleValueSeparator()
        );
    }

    /**
     * @param $values
     * @param string $delimiter
     * @return array
     */
    public function parseMultiselectValues($values, $delimiter = Product::PSEUDO_MULTI_LINE_SEPARATOR)
    {
        if (empty($this->config[Import::FIELDS_ENCLOSURE])) {
            return explode($delimiter, $values);
        }
        if (preg_match_all('~"((?:[^"]|"")*)"~', $values, $matches)) {
            return $values = array_map(
                function ($value) {
                    return str_replace('""', '"', $value);
                },
                $matches[1]
            );
        }
        return [$values];
    }

    /**
     * @param array $rowData
     *
     * @return mixed
     */
    public function getCorrectSkuAsPerLength(array $rowData)
    {
        return strlen($rowData[Product::COL_SKU]) > Sku::SKU_MAX_LENGTH ?
            substr($rowData[Product::COL_SKU], 0, Sku::SKU_MAX_LENGTH) : $rowData[Product::COL_SKU];
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getMediaGalleryAttributeId()
    {
        if (!$this->mediaGalleryAttributeId) {
            /** @var $resource \Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModel */
            $resource = $this->resourceModelFactory->create();
            $this->mediaGalleryAttributeId =
                $resource->getAttribute(Product::MEDIA_GALLERY_ATTRIBUTE_CODE)->getId();
        }
        return $this->mediaGalleryAttributeId;
    }

    /**
     * @param $existingImages
     * @param $columnImage
     * @param $rowSku
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function checkAlreadyUploadedImages(&$existingImages, $columnImage, $rowSku)
    {
        $alreadyUploadedFile = $existingImages[$rowSku][$columnImage]['value'] ?? '';
        $isUploaded = '';
        if (empty($alreadyUploadedFile)) {
            $uploadedFileName = hash('sha256', $columnImage);
            $isUploaded = $this->getUploader()::getDispersionPath($uploadedFileName)
                . DIRECTORY_SEPARATOR . $uploadedFileName;
        }
        $isAlreadyUploaded = false;
        foreach ($this->getUploader()->getAllowedFileExtension() as $fileExtension) {
            if (!empty($isUploaded)) {
                $alreadyUploadedFile = $isUploaded . '.' . $fileExtension;
            }
            if (array_key_exists($rowSku, $existingImages)
                && array_key_exists($alreadyUploadedFile, $existingImages[$rowSku])
            ) {
                $isAlreadyUploaded = true;
                break;
            }
        }
        return [$isAlreadyUploaded, $alreadyUploadedFile];
    }

    /**
     * @param string $fileName
     * @param bool $renameFileOff
     * @param array $existingUpload
     *
     * @return string
     */
    public function uploadMediaFiles($fileName, $renameFileOff = false, $existingUpload = [])
    {
        $uploadedFile = '';
        try {
            $result = $this->getUploader()->move($fileName, $renameFileOff, $existingUpload);
            if (!empty($result)) {
                $uploadedFile = $result['file'];
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
            $uploadedFile = null;
        }
        return $uploadedFile;
    }

    /**
     * @return \Magento\CatalogImportExport\Model\Import\Uploader
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function getUploader()
    {
        $DS = DIRECTORY_SEPARATOR;
        if ($this->fileUploader === null) {
            $this->fileUploader = $this->uploaderFactory->create();
            $this->fileUploader->init();
            $this->fileUploader->setEntity($this);
            $dirConfig = DirectoryList::getDefaultConfig();
            $dirAddon = $dirConfig[DirectoryList::MEDIA][DirectoryList::PATH];
            if (!empty($this->config[Import::FIELD_NAME_IMG_FILE_DIR])) {
                $tmpPath = $this->config[Import::FIELD_NAME_IMG_FILE_DIR];
            } else {
                $tmpPath = $dirAddon . $DS . $this->mediaDirectory->getRelativePath('import');
            }
            if (preg_match('/\bhttps?:\/\//i', $tmpPath, $matches)) {
                $tmpPath = $dirAddon . $DS . $this->mediaDirectory->getRelativePath('import');
            }
            if (!$this->fileUploader->setTmpDir($tmpPath)) {
                throw new LocalizedException(
                    __('File directory \'%1\' is not readable.', $tmpPath)
                );
            }
            $destinationDir = "catalog/product";
            $destinationPath = $dirAddon . $DS . $this->mediaDirectory->getRelativePath($destinationDir);

            $this->mediaDirectory->create($destinationPath);
            if (!$this->fileUploader->setDestDir($destinationPath)) {
                throw new LocalizedException(
                    __('File directory \'%1\' is not writable.', $destinationPath)
                );
            }
        }

        return $this->fileUploader;
    }

    /**
     * @return ProcessingErrorAggregatorInterface
     */
    public function getErrorAggregator()
    {
        return $this->errorAggregator;
    }

    /**
     * Add error with corresponding current data source row number.
     *
     * @param string $errorCode Error code or simply column name
     * @param int $errorRowNum Row number.
     * @param string $colName OPTIONAL Column name.
     * @param string $errorMessage OPTIONAL Column name.
     * @param string $errorLevel
     * @param string $errorDescription
     * @return $this
     */
    public function addRowError(
        $errorCode,
        $errorRowNum,
        $colName = null,
        $errorMessage = null,
        $errorLevel = ProcessingError::ERROR_LEVEL_CRITICAL,
        $errorDescription = null
    ) {
        $errorCode = (string)$errorCode;
        $this->getErrorAggregator()->addError(
            $errorCode,
            $errorLevel,
            $errorRowNum,
            $colName,
            $errorMessage,
            $errorDescription
        );

        return $this;
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

    /**
     * @return array
     */
    public function getBunchUploadedImages()
    {
        return $this->bunchUploadedImages;
    }

    /**
     * @param $bunch
     * @return array
     * @throws \Exception
     */
    public function getExistingAttributeImages($bunch): array
    {
        $mediaImagesAttributes = $this->getMediaImagesAttributes();
        $resource = $this->getResource();
        $conn = $this->getResource()->getConnection();
        $query = $conn->select()->from(
            ['cpe' => $resource->getTable('catalog_product_entity')],
            ['sku' => 'cpe.sku', 'store_code' => 'st.code', 'image' => 'cpev.value']
        )->join(
            ['cpev' => $resource->getTable('catalog_product_entity_varchar')],
            'cpe.' . $this->getProductEntityLinkField() . '=' . 'cpev.' . $this->getProductEntityLinkField()
        )->join(['ea' => $resource->getTable('eav_attribute')], 'cpev.attribute_id=ea.attribute_id')
            ->join(['st' => $resource->getTable('store')], 'cpev.store_id=st.store_id')
            ->where('ea.attribute_code IN(?)', $mediaImagesAttributes)
            ->where('cpe.sku IN(?)', array_column($bunch, Product::COL_SKU));
        $result = [];
        foreach ($conn->fetchAll($query) as $item) {
            $result[$item['sku']][$item['image']][$item['store_code']][] = [
                'value_id' => $item['value_id'],
                'store_id' => $item['store_id'],
                'attribute_id' => $item['attribute_id'],
            ];
        }
        return $result;
    }
}
