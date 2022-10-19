<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export;

use DateTime;
use Exception;
use Firebear\ImportExport\Model\Export\Product\Additional;
use Firebear\ImportExport\Model\Export\RowCustomizer\ProductVideo;
use Firebear\ImportExport\Model\ExportJob\Processor;
use Firebear\ImportExport\Traits\Export\Entity as ExportTrait;
use Firebear\ImportExport\Api\Data\SeparatorFormatterInterface;
use IntlDateFormatter;
use Magento\Catalog\Model\Product\LinkTypeProvider;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\ProductFactory;
use Magento\CatalogImportExport\Model\Export\Product\Type\Factory;
use Magento\CatalogImportExport\Model\Export\RowCustomizerInterface;
use Magento\CatalogImportExport\Model\Import\Product as ImportProduct;
use Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Collection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Module\Manager;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\ImportExport\Model\Export;
use Magento\ImportExport\Model\Export\ConfigInterface;
use Magento\ImportExport\Model\Import;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Swatches\Helper\Data;
use Magento\Swatches\Model\ResourceModel\Swatch\CollectionFactory as SwatchCollectionFactory;
use Magestore\InventorySuccess\Api\Warehouse\WarehouseStockRegistryInterface;
use Psr\Log\LoggerInterface;
use Zend_Db_Statement_Exception;
use function array_keys;
use function array_merge;

/**
 * Class Product
 *
 * @package Firebear\ImportExport\Model\Export
 */
class Product extends \Magento\CatalogImportExport\Model\Export\Product implements EntityInterface
{
    use ExportTrait;

    const CACHE_TAG = 'config_scopes';
    const COL_CATEGORY_IDS = 'category_ids';
    const COLUMN_ID = 'opt_id';
    const COLUMN_ROW_ID = 'opt_row_id';

    /**
     * @var array
     */
    protected $attributeStoreValues = [];

    protected $headColumns;

    protected $additional;
    /**
     * @var SwatchCollectionFactory
     */
    protected $swatchCollectionFactory;
    /**
     * @var Data
     */
    protected $swatchesHelperData;

    private $userDefinedAttributes = [];

    protected $keysAdditional;

    /** @var Manager */
    protected $moduleManager;

    /** @var string */
    protected $multipleValueSeparator;

    /**
     * Product media gallery cache
     *
     * @var array[]
     */
    protected $mediaGalleryCache = [];

    /**
     * @var CacheInterface
     */
    protected $cache;

    /** @var array */
    private $cachedSwatchOptions = [];

    /**
     * Total entities limit to be fetched during export job. NULL to disable
     *
     * @var null|int
     */
    private $totalEntitiesLimit = null;

    /**
     * @var array|null
     */
    private $stores = null;

    /**
     * @var array
     */
    private $isLastPageExported = [];

    /**
     * Json Serializer
     *
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var Product\AdditionalFieldsPool
     */
    protected $additionalFieldsPool;

    /**
     * Attribute labels
     *
     * @var array
     */
    protected $attributeLabels = [];

    /**
     * Tier Price Attributes
     *
     * @var array
     */
    protected $tierPriceAttributes = [
        'tier_prices:fixed',
        'tier_prices:quantity',
        'tier_prices:discount'
    ];

    /**
     * @var SeparatorFormatterInterface
     */
    private $separatorFormatter;

    /**
     * Product constructor.
     * @param TimezoneInterface $localeDate
     * @param Config $config
     * @param ResourceConnection $resource
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory
     * @param ConfigInterface $exportConfig
     * @param ProductFactory $productFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attrSetColFactory
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryColFactory
     * @param ItemFactory $itemFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory $optionColFactory
     * @param CollectionFactory $attributeColFactory
     * @param Factory $_typeFactory
     * @param LinkTypeProvider $linkTypeProvider
     * @param RowCustomizerInterface $rowCustomizer
     * @param Additional $additional
     * @param Manager $moduleManager
     * @param Data $swatchesHelperData
     * @param SwatchCollectionFactory $swatchCollectionFactory
     * @param CacheInterface $cache
     * @param SerializerInterface $serializer
     * @param Product\AdditionalFieldsPool $additionalFieldsPool
     * @param SeparatorFormatterInterface $separatorFormatter
     * @param array $dateAttrCodes
     * @throws LocalizedException
     */
    public function __construct(
        TimezoneInterface $localeDate,
        Config $config,
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
        ConfigInterface $exportConfig,
        ProductFactory $productFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attrSetColFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryColFactory,
        ItemFactory $itemFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory $optionColFactory,
        CollectionFactory $attributeColFactory,
        Factory $_typeFactory,
        LinkTypeProvider $linkTypeProvider,
        RowCustomizerInterface $rowCustomizer,
        Product\Additional $additional,
        Manager $moduleManager,
        Data $swatchesHelperData,
        SwatchCollectionFactory $swatchCollectionFactory,
        CacheInterface $cache,
        SerializerInterface $serializer,
        Product\AdditionalFieldsPool $additionalFieldsPool,
        SeparatorFormatterInterface $separatorFormatter,
        array $dateAttrCodes = []
    ) {
        $this->swatchCollectionFactory = $swatchCollectionFactory;
        $this->swatchesHelperData = $swatchesHelperData;
        $this->_fieldsMap += [self::COL_CATEGORY . '_position' => $this->_fieldsMap[self::COL_CATEGORY] . '_position'];
        $this->additionalFieldsPool = $additionalFieldsPool;
        $this->separatorFormatter = $separatorFormatter;

        parent::__construct(
            $localeDate,
            $config,
            $resource,
            $storeManager,
            $logger,
            $collectionFactory,
            $exportConfig,
            $productFactory,
            $attrSetColFactory,
            $categoryColFactory,
            $itemFactory,
            $optionColFactory,
            $attributeColFactory,
            $_typeFactory,
            $linkTypeProvider,
            $rowCustomizer,
            $dateAttrCodes
        );

        $this->additional = $additional;
        $this->moduleManager = $moduleManager;
        $this->multipleValueSeparator = Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR;
        $this->cache = $cache;
        $this->serializer = $serializer;
    }

    /**
     * Initialize attribute option values and types.
     *
     * @return $this
     * @throws LocalizedException
     */
    protected function initAttributes()
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute */
        foreach ($this->getAttributeCollection() as $attribute) {
            if ($attribute->usesSource() && $this->isInNamespace('Temando', $attribute->getSource())) {
                // Skip Temando attributes
                continue;
            }

            $options = $this->getAttributeOptions($attribute);
            $this->attributeLabels[$attribute->getAttributeCode()] = $attribute->getFrontendLabel();
            $this->_attributeValues[$attribute->getAttributeCode()] = isset($options[Store::DEFAULT_STORE_ID])
                ? $options[Store::DEFAULT_STORE_ID] : [];
            $this->attributeStoreValues[$attribute->getAttributeCode()] = $options;
            $this->_attributeTypes[$attribute->getAttributeCode()] =
                Import::getAttributeType($attribute);
            if ($attribute->getIsUserDefined()) {
                $this->userDefinedAttributes[] = $attribute->getAttributeCode();
            }
        }
        return $this;
    }

    /**
     * Retrieve entity field for filter
     *
     * @return array
     */
    public function getFieldsForFilter()
    {
        $fields = $this->additional->toOptionArray();
        foreach ($this->attributeLabels as $value => $label) {
            if ($value == 'tier_price') {
                continue;
            }
            $fields[] = ['value' => $value, 'label' => $label];
        }
        return [$this->getEntityTypeCode() => $fields];
    }

    /**
     * Retrieve entity field columns
     *
     * @return array
     * @throws LocalizedException
     */
    public function getFieldColumns()
    {
        $fields = $this->additional->getAdditionalFields();
        foreach ($this->_attributeTypes as $field => $type) {
            if ($field == 'tier_price') {
                continue;
            }
            $option = [];
            foreach ($this->_attributeValues[$field] ?? [] as $value => $label) {
                $option[] = ['value' => $value, 'label' => $label];
            }
            $fields[] = [
                'field' => $field,
                'type' => ($field == self::COL_CATEGORY_IDS) ? 'range' : $this->getAttributeType($type),
                'select' => $option
            ];
        }
        return [$this->getEntityTypeCode() => $fields];
    }

    /**
     * Check if the object belongs to namespace
     *
     * @param string $namespace
     * @param mixed $object
     * @return bool
     */
    protected function isInNamespace($namespace, $object)
    {
        $class = get_class($object);
        return strpos($class, $namespace . '\\') === 0;
    }

    /**
     * Plugins may be attached. Be careful in renaming this function
     *
     * @return array
     */
    public function getExportData()
    {
        $exportData = [];
        try {
            $rawData = $this->collectRawData();
            $multirawData = $this->collectMultirawData();

            $productIds = array_keys($rawData);

            $stockItemRows = $this->prepareCatalogInventory($productIds);

            $this->rowCustomizer->prepareData(
                $this->_prepareEntityCollection($this->_entityCollectionFactory->create()),
                $productIds
            );

            $this->clearMediaGalleryCache();
            $this->warmUpMediaGalleryCache(array_keys($rawData));

            $this->setAddHeaderColumns($stockItemRows);
            $rawData = $this->addAdditionalFields($rawData);
            $prevData = [];
            foreach ($rawData as $productId => $productData) {
                foreach ($productData as $storeId => $dataRow) {
                    if (isset($stockItemRows[$productId])) {
                        $dataRow = array_merge($dataRow, $stockItemRows[$productId]);
                    }
                    $this->appendMultirowData($dataRow, $multirawData);

                    if ($dataRow) {
                        if (Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR !== $this->multipleValueSeparator) {
                            $fields = [
                                'bundle_values',
                                'configurable_variations',
                                'configurable_variation_labels'
                            ];

                            foreach ($fields as $field) {
                                if (!empty($dataRow[$field])) {
                                    $dataRow[$field] = str_replace(
                                        Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR,
                                        $this->multipleValueSeparator,
                                        $dataRow[$field]
                                    );
                                }
                            }
                        }

                        if (!empty($prevData)) {
                            if (isset($prevData['sku']) && isset($dataRow['sku'])) {
                                if ($prevData['sku'] == $dataRow['sku']) {
                                    $dataRow = array_merge($prevData, $dataRow);
                                }
                            }
                        }
                        $exportData[] = $dataRow;
                    }
                    $prevData = $dataRow;
                }
            }
        } catch (Exception $e) {
            $this->_logger->critical($e);
        }
        $newData = $this->changeData($exportData, 'product_id');
        $this->addHeaderColumns();
        $this->_headerColumns = $this->changeHeaders($this->_headerColumns);

        return $newData;
    }

    /**
     * Prepare processor list data parameters
     */
    protected function prepareProcessorListDataParam()
    {
        if (!empty($this->_parameters[Processor::LIST_DATA])) {
            $processorListData = [];
            $flipFieldsMap = array_flip($this->_fieldsMap);
            foreach ($this->_parameters[Processor::LIST_DATA] as $field) {
                $field = $flipFieldsMap[$field] ?? $field;
                $processorListData[] = $field;
            }
            $this->_parameters[Processor::LIST_DATA] = $processorListData;
        }
    }

    protected function _customHeadersMapping($rowData)
    {
        $rowData = parent::_customHeadersMapping($rowData);

        return ($this->_parameters[Processor::ALL_FIELDS]) ? $this->_headerColumns : array_unique($rowData);
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function export()
    {
        $this->keysAdditional = [];

        set_time_limit(0);

        $writer = $this->getWriter();
        $page = 0;
        $this->_parameters['export_by_page'] = true;
        $this->prepareProcessorListDataParam();
        $jobId = $this->_parameters['job_id'] ?? '';

        $counts = 0;
        if (!empty($this->_parameters[Processor::BEHAVIOR_DATA]['multiple_value_separator'])) {
            $this->multipleValueSeparator = $this->separatorFormatter->format(
                $this->_parameters[Processor::BEHAVIOR_DATA]['multiple_value_separator']
            );
        }

        if (!empty($this->_parameters[Processor::BEHAVIOR_DATA]['export_by_page']) &&
            $this->_parameters[Processor::BEHAVIOR_DATA]['file_format'] == 'csv') {
            $page = $this->cache->load('current_page');

            if ($page == 1) {
                $this->cacheSave(null, 'last_page_exported');
            }
            $isAllStoresExported = $this->getAllStoresExported();
            if (!$isAllStoresExported) {
                $entityCollection = $this->_getEntityCollection(true);
                $entityCollection->setOrder('entity_id', 'asc');
                $entityCollection->setStoreId(Store::DEFAULT_STORE_ID);
                if (isset($this->_parameters[Processor::LAST_ENTITY_ID])
                    && $this->_parameters[Processor::LAST_ENTITY_ID] > 0
                    && $this->_parameters[Processor::LAST_ENTITY_SWITCH] > 0
                ) {
                    $entityCollection->addFieldToFilter(
                        'entity_id',
                        ['gt' => $this->_parameters[Processor::LAST_ENTITY_ID]]
                    );
                }
                $this->_prepareEntityCollection($entityCollection);

                if (!empty($this->_parameters[Processor::BEHAVIOR_DATA]['page_size'])) {
                    $pageSize = $this->_parameters[Processor::BEHAVIOR_DATA]['page_size'];
                } else {
                    $pageSize = 500;
                }

                $this->paginateCollection($page, $pageSize);

                if ($entityCollection->count()) {
                    $exportData = $this->getExportData();
                    if ($page == 1) {
                        $writer->setHeaderCols($this->_getHeaderColumns());
                    }
                    $exportData = $this->customBunchesData($exportData);
                    foreach ($exportData as $dataRow) {
                        if ($this->_parameters[Processor::LAST_ENTITY_SWITCH] > 0) {
                            $this->lastEntityId = $dataRow['product_id'];
                        }
                        $writer->writeRow($this->_customFieldsMapping($dataRow));
                        $counts++;
                    }
                }

                $isAllStoresExported = $this->getAllStoresExported();
                if ($page == $entityCollection->getLastPageNumber() || $isAllStoresExported) {
                    $this->cacheSave(0, 'export_by_page' . $jobId);
                } else {
                    $this->cacheSave(1, 'export_by_page' . $jobId);
                }
            }
        } else {
            while (true) {
                ++$page;

                $entityCollection = $this->_getEntityCollection(true);
                $entityCollection->setOrder('entity_id', 'asc');
                $entityCollection->setStoreId(Store::DEFAULT_STORE_ID);
                if ($page == 1) {
                    $this->cacheSave(null, 'last_page_exported');
                }
                if (isset($this->_parameters[Processor::LAST_ENTITY_ID])
                    && $this->_parameters[Processor::LAST_ENTITY_ID] > 0
                    && $this->_parameters[Processor::LAST_ENTITY_SWITCH] > 0
                ) {
                    $entityCollection->addFieldToFilter(
                        'entity_id',
                        ['gt' => $this->_parameters[Processor::LAST_ENTITY_ID]]
                    );
                }

                $this->_prepareEntityCollection($entityCollection);
                $this->paginateCollection($page, $this->getItemsPerPage());

                $entitiesCount = $entityCollection->count();
                if ($entitiesCount == 0) {
                    break;
                }

                $this->cache->save($entitiesCount, 'export_entities_count');

                $exportData = $this->getExportData();
                if ($page == 1) {
                    $writer->setHeaderCols($this->_getHeaderColumns());
                }

                $exportData = $this->customBunchesData($exportData);
                foreach ($exportData as $dataRow) {
                    if ($this->_parameters[Processor::LAST_ENTITY_SWITCH] > 0) {
                        $this->lastEntityId = $dataRow['product_id'];
                    }
                    $writer->writeRow($this->_customFieldsMapping($dataRow));
                    $counts++;
                }

                if ($this->isCollectionLastPage($entityCollection)) {
                    break;
                }
            }

            $this->cache->remove('export_entities_count');
        }

        return [$writer->getContents(), $counts, $this->lastEntityId];
    }

    /**
     * @inheritdoc
     */
    protected function getItemsPerPage()
    {
        if (!empty($this->_parameters[Processor::BEHAVIOR_DATA]['page_size'])) {
            $pageSize = $this->_parameters[Processor::BEHAVIOR_DATA]['page_size'];
        } else {
            $pageSize = 500;
            $default = parent::getItemsPerPage();
            if ($default < $pageSize) {
                $pageSize = $default;
            }
        }

        return $pageSize;
    }

    /**
     * Make it public to allow usage of this method in plugins.
     * Plugins may be attached. Be careful in renaming this function
     *
     * phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found
     * @inheritdoc
     */
    public function _getEntityCollection($resetCollection = false)
    {
        return parent::_getEntityCollection($resetCollection);
    }

    /**
     * Add support for total entities limit
     * Plugins may be attached. Be careful in renaming this function
     *
     * @inheritdoc
     */
    public function paginateCollection($page, $pageSize)
    {
        $totalLimit = $this->getTotalEntitiesLimit();
        if ($totalLimit !== null && $pageSize > $totalLimit) {
            $pageSize = $totalLimit;
        }

        parent::paginateCollection($page, $pageSize);
    }

    /**
     * Set total entities limit to be fetched during export.
     * Set to null to disable
     * Plugins may be attached. Be careful in renaming this function
     *
     * @param int $totalEntitiesLimit
     * @return Product
     */
    public function setTotalEntitiesLimit($totalEntitiesLimit)
    {
        $this->totalEntitiesLimit = $totalEntitiesLimit;
        return $this;
    }

    /**
     * Get total entities limit to be fetched during export.
     *
     * @return int|null
     */
    protected function getTotalEntitiesLimit()
    {
        return $this->totalEntitiesLimit;
    }

    /**
     * Is collection last page
     *
     * @param \Magento\Framework\Data\Collection\AbstractDb $entityCollection
     * @return bool
     */
    protected function isCollectionLastPage($entityCollection)
    {
        $pageSize = $entityCollection->getPageSize();
        $currentPage = $entityCollection->getCurPage();

        return $currentPage >= $entityCollection->getLastPageNumber()
            || $this->totalEntitiesLimit !== null
            && $currentPage * $pageSize >= $this->totalEntitiesLimit;
    }

    /**
     * Plugins may be attached. Be careful in renaming this function
     *
     * @param array $rowData
     * @return array
     */
    public function _customFieldsMapping($rowData)
    {
        $headerColumns = $this->_getHeaderColumns();

        $rowData = parent::_customFieldsMapping($rowData);
        if (count($headerColumns) != count(array_keys($rowData))) {
            $newData = [];
            foreach ($headerColumns as $code) {
                $fieldCode = isset($this->_fieldsMap[$code]) ? $this->_fieldsMap[$code] : null;
                if ($fieldCode && isset($rowData[$fieldCode])) {
                    $newData[$code] = $rowData[$fieldCode];
                } else {
                    if (!isset($rowData[$code])) {
                        $newData[$code] = '';
                    } else {
                        $newData[$code] = $rowData[$code];
                    }
                }
            }
            $rowData = $newData;
        }

        return $rowData;
    }

    /**
     * @inheritdoc
     */
    private function joinTierPriceTable(AbstractCollection $collection)
    {
        $joinField = $this->getProductEntityLinkField();
        $collection->distinct(true)->getSelect()->join(
            ['tp' => $collection->getResource()->getTable('catalog_product_entity_tier_price')],
            "e.{$joinField} = tp.{$joinField}",
            []
        );
    }

    /**
     * Plugins may be attached. Be careful in renaming this function
     *
     * @inheritdoc
     */
    public function _prepareEntityCollection(AbstractCollection $collection)
    {
        if (!isset($this->_parameters[Export::FILTER_ELEMENT_GROUP])
            || !is_array($this->_parameters[Export::FILTER_ELEMENT_GROUP])) {
            $exportFilter = [];
        } else {
            $exportFilter = $this->_parameters[Export::FILTER_ELEMENT_GROUP];
            /* The value of multiselect filter must be an array */
            foreach ($this->filterAttributeCollection($this->getAttributeCollection()) as $attribute) {
                $attrCode = $attribute->getAttributeCode();
                if (isset($exportFilter[$attrCode])) {
                    $attrFilterType = Export::getAttributeFilterType($attribute);
                    if (Export::FILTER_TYPE_MULTISELECT == $attrFilterType &&
                        !is_array($exportFilter[$attrCode])
                    ) {
                        /* delegate new value to parent handler */
                        $this->_parameters[Export::FILTER_ELEMENT_GROUP][$attrCode] = [$exportFilter[$attrCode]];
                    }
                }
            }
        }

        $collection = Export\Entity\AbstractEntity::_prepareEntityCollection($collection);

        foreach ($this->additional->fields as $field) {
            if (isset($exportFilter[$field]) && !empty($exportFilter[$field])) {
                if ($field == 'store') {
                    $collection->addStoreFilter($exportFilter['store']);
                } else {
                    if (in_array($field, $this->tierPriceAttributes)) {
                        $this->joinTierPriceTable($collection);
                    }
                    $collection->getSelect()->where(
                        $this->additional->convertFields($field) . "=?",
                        $exportFilter[$field]
                    );
                }
            }
        }

        if (isset($exportFilter['category_ids']) &&
            is_array($exportFilter['category_ids']) &&
            count($exportFilter['category_ids']) >= 2) {
            $from = array_shift($exportFilter['category_ids']);
            $to = array_shift($exportFilter['category_ids']);
            if (!empty($exportFilter['category_ids'])) {
                $exclude = array_shift($exportFilter['category_ids']);
            }

            $categorySelect = $collection->getConnection()->select()->from(
                ['cat' => $collection->getResource()->getTable('catalog_category_product')],
                'cat.product_id'
            );

            if (is_numeric($from)) {
                $categorySelect->where('cat.category_id >= ?', $from);
            }
            if (is_numeric($to)) {
                $categorySelect->where('cat.category_id <= ?', $to);
            }
            if (!empty($exclude)) {
                $exclude = explode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $exclude);
                $categorySelect->where('cat.category_id NOT IN (?)', $exclude);
            }

            $collection->getSelect()->where(
                $collection->getConnection()->prepareSqlCondition(
                    'e.entity_id',
                    ['in' => $categorySelect]
                )
            );
        }

        $attrCode = 'quantity_and_stock_status';
        if (isset($exportFilter[$attrCode])) {
            $inStock = (int)$exportFilter[$attrCode];
            $collection->getSelect()
                ->join(
                    ['csi' => $collection->getTable('cataloginventory_stock_item')],
                    'csi.product_id = e.entity_id',
                    ['is_in_stock']
                )
                ->where('is_in_stock =?', $inStock);
        }

        return $collection;
    }

    protected function collectMultirawData()
    {
        $productIds = [];
        $rowWebsites = [];
        $rowCategoriesPosition = [];
        $productLinkIds = [];

        $entityCollection = $this->_getEntityCollection();
        $entityCollection->setStoreId(Store::DEFAULT_STORE_ID);
        $entityCollection->addCategoryIds()->addWebsiteNamesToResult();
        $this->addCategoryPosition($entityCollection);

        /** @var \Magento\Catalog\Model\Product $item */
        foreach ($entityCollection as $item) {
            $productLinkIds[] = $item->getData($this->getProductEntityLinkField());
            $productIds[] = $item->getId();
            $rowWebsites[$item->getId()] = array_intersect(
                array_keys($this->_websiteIdToCode),
                $item->getWebsites()
            );

            $rowCategoriesPosition[$item->getId()] = $item->getCategoryPosition();
        }
        $entityCollection->clear();

        $rowCategories = $this->getRowCategoriesData($productIds);
        $categoryIds = array_merge(array_keys($this->_categories), array_keys($this->_rootCategories));
        $categoryIds = array_combine($categoryIds, $categoryIds);
        foreach ($rowCategories as &$categories) {
            $categories = array_intersect_key($categories, $categoryIds);
        }

        $data['rowWebsites'] = $rowWebsites;
        $data['rowCategories'] = $rowCategories;
        $data['rowCategoriesPosition'] = $rowCategoriesPosition;
        $data['rowCategoryIds'] = $rowCategories;
        $data['linksRows'] = $this->prepareLinks($productLinkIds);
        $data['customOptionsData'] = $this->getCustomOptionsData($productLinkIds);
        return $data;
    }

    /**
     * @param array $rows
     * @return array
     */
    protected function addAdditionalFields(array $rows): array
    {
        foreach ($this->additionalFieldsPool->getEntities() as $keyEntity => $entity) {
            $entity->addFields($rows);
            $this->_headerColumns = array_merge($this->_headerColumns, $entity->getHeaders());
        }
        return $rows;
    }

    /**
     * @param int[] $productIds
     * @return array
     */
    protected function getCustomOptionsData($productIds)
    {
        $customOptionsData = [];
        $defaultOptionsData = [];

        foreach (array_keys($this->_storeIdToCode) as $storeId) {
            $options = $this->_optionColFactory->create();
            /* @var Collection $options*/
            $options->reset()
                ->addOrder('sort_order', Collection::SORT_ORDER_ASC)
                ->addTitleToResult($storeId)
                ->addPriceToResult($storeId)
                ->addProductToFilter($productIds)
                ->addValuesToResult($storeId);

            foreach ($options as $option) {
                $optionData = $option->toArray();
                $row = [];
                $productId = $option['product_id'];
                $row['name'] = $option['title'];
                $row['type'] = $option['type'];
                $row[self::COLUMN_ID] = $option['option_id'];

                $row['required'] = $this->getOptionValue('is_require', $defaultOptionsData, $optionData);
                $row['price'] = $this->getOptionValue('price', $defaultOptionsData, $optionData);
                $row['sku'] = $this->getOptionValue('sku', $defaultOptionsData, $optionData);
                if (array_key_exists('max_characters', $optionData)
                    || array_key_exists('max_characters', $defaultOptionsData)
                ) {
                    $row['max_characters'] = $this->getOptionValue('max_characters', $defaultOptionsData, $optionData);
                }
                foreach (['file_extension', 'image_size_x', 'image_size_y'] as $fileOptionKey) {
                    if (isset($option[$fileOptionKey]) || isset($defaultOptionsData[$fileOptionKey])) {
                        $row[$fileOptionKey] = $this->getOptionValue($fileOptionKey, $defaultOptionsData, $optionData);
                    }
                }
                $percentType = $this->getOptionValue('price_type', $defaultOptionsData, $optionData);
                $row['price_type'] = ($percentType === 'percent') ? 'percent' : 'fixed';

                if (Store::DEFAULT_STORE_ID === $storeId) {
                    $optionId = $option['option_id'];
                    $defaultOptionsData[$optionId] = $option->toArray();
                }

                $values = $option->getValues();

                if ($values) {
                    foreach ($values as $value) {
                        $row['option_title'] = $value['title'];
                        $row['option_title'] = $value['title'];
                        $row['price'] = $value['price'];
                        $row['price_type'] = ($value['price_type'] === 'percent') ? 'percent' : 'fixed';
                        $row['sku'] = $value['sku'];
                        $row[self::COLUMN_ROW_ID] = $value['option_type_id'];
                        $customOptionsData[$productId][$storeId][] = $this->optionRowToCellString($row);

                    }
                } else {
                    $customOptionsData[$productId][$storeId][] = $this->optionRowToCellString($row);
                }
                $option = null;
            }
            $options = null;
        }

        return $customOptionsData;
    }

    /**
     * @param string $optionName
     * @param array $defaultOptionsData
     * @param array $optionData
     * @return mixed|null
     */
    private function getOptionValue($optionName, $defaultOptionsData, $optionData)
    {
        $optionId = $optionData['option_id'];

        if (array_key_exists($optionName, $optionData) && $optionData[$optionName] !== null) {
            return $optionData[$optionName];
        }

        if (array_key_exists($optionId, $defaultOptionsData)
            && array_key_exists($optionName, $defaultOptionsData[$optionId])
        ) {
            return $defaultOptionsData[$optionId][$optionName];
        }

        return null;
    }

    /**
     * Get row category data by product ids
     *
     * @param array $productIds
     * @return array
     */
    protected function getRowCategoriesData($productIds)
    {
        $rowCategories = [];
        if (empty($productIds)) {
            return $rowCategories;
        }

        $select = $this->_resourceModel->getConnection()->select();
        $tableName = $this->_resourceModel->getTableName('catalog_category_product');
        $select->from($tableName, [
            'product_id',
            'category_ids' => new \Zend_Db_Expr('GROUP_CONCAT(category_id)')
        ]);
        $select->where('product_id IN (?)', $productIds);
        $select->group('product_id');

        $data = $this->_resourceModel->getConnection()->fetchAll($select);
        foreach ($data as $row) {
            $categories = explode(',', $row['category_ids']);
            $rowCategories[$row['product_id']] = array_combine($categories, $categories);
        }

        return $rowCategories;
    }

    /**
     * Add category position to loaded items
     *
     * @param AbstractCollection $collection
     * @return AbstractCollection
     */
    public function addCategoryPosition($collection)
    {
        if ($collection->getFlag('category_position_added')) {
            return $collection;
        }

        $productIds = [];
        foreach ($collection as $item) {
            $productIds[] = $item->getId();
        }

        if (!$productIds) {
            return $collection;
        }

        $select = $collection->getConnection()->select();

        $tableName = $collection->getResource()->getTable('catalog_category_product');
        $select->from($tableName, ['product_id', 'category_id', 'position']);
        $select->where('product_id IN (?)', $productIds);
        $data = $collection->getConnection()->fetchAll($select);

        $categoryPosition = [];
        foreach ($data as $info) {
            $categoryPosition[$info['product_id']][$info['category_id']] = $info['position'];
        }

        foreach ($collection as $item) {
            $productId = $item->getId();
            if (isset($categoryPosition[$productId])) {
                $item->setCategoryPosition($categoryPosition[$productId]);
            } else {
                $item->setCategoryPosition([]);
            }
        }

        $collection->setFlag('category_position_added', true);
        return $collection;
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    protected function fieldsCatalogInventory()
    {
        $fields = $this->_connection->describeTable($this->_itemFactory->create()->getMainTable());
        $rows = [];
        $row = [];
        unset(
            $fields['item_id'],
            $fields['product_id'],
            $fields['low_stock_date'],
            $fields['stock_id'],
            $fields['stock_status_changed_auto']
        );
        foreach ($fields as $key => $field) {
            $row[$key] = $key;
        }

        $rows[] = $row;
        return $rows;
    }

    protected function collectRawData()
    {
        $data = [];
        $items = $this->fireloadCollection();
        $stores = $this->getStores();

        foreach ($items as $itemId => $itemByStore) {
            /**
             * @var int $itemId
             * @var ProductEntity $item
             */
            foreach ($stores as $storeId => $storeCode) {
                if (!isset($itemByStore[$storeId])) {
                    continue;
                }
                /** @var \Magento\Catalog\Model\Product $item */
                $item = $itemByStore[$storeId];
                $addtionalFields = [];
                $additionalAttributes = [];
                $productLinkId = $item->getData($this->getProductEntityLinkField());

                $exportAttrCodes = array_unique($this->_getExportAttrCodes());
                foreach ($exportAttrCodes as $attrCodes) {
                    $attrValue = $item->getData($attrCodes);
                    if (isset($this->_attributeTypes[$attrCodes]) &&
                        $this->_attributeTypes[$attrCodes] != 'text' &&
                        !empty($attrValue)
                    ) {
                        $attrValue = str_replace(["\r\n", "\n\r", "\n", "\r"], '', $attrValue);
                    }
                    if (!$this->isValidAttributeValue($attrCodes, $attrValue)) {
                        continue;
                    }

                    if (isset($this->attributeStoreValues[$attrCodes][$storeId][$attrValue])
                        && !empty($this->attributeStoreValues[$attrCodes][$storeId])
                    ) {
                        $attrValue = $this->attributeStoreValues[$attrCodes][$storeId][$attrValue];
                    }
                    $fieldName = isset($this->_fieldsMap[$attrCodes]) ? $this->_fieldsMap[$attrCodes] : $attrCodes;

                    if ($this->_attributeTypes[$attrCodes] == 'datetime') {
                        if (in_array($attrCodes, $this->dateAttrCodes) ||
                            in_array($attrCodes, $this->userDefinedAttributes)) {
                            $attrValue = $this->_localeDate
                                ->formatDateTime(
                                    new DateTime($attrValue),
                                    IntlDateFormatter::SHORT,
                                    IntlDateFormatter::NONE,
                                    null,
                                    date_default_timezone_get()
                                );
                        } else {
                            $attrValue = $this->_localeDate
                                ->formatDateTime(
                                    new DateTime($attrValue),
                                    IntlDateFormatter::SHORT,
                                    IntlDateFormatter::SHORT
                                );
                        }
                    }

                    if ($storeId != Store::DEFAULT_STORE_ID
                        && isset($data[$itemId][Store::DEFAULT_STORE_ID][$fieldName])
                        && $data[$itemId][Store::DEFAULT_STORE_ID][$fieldName] == htmlspecialchars_decode($attrValue)
                    ) {
                        continue;
                    }

                    if ($this->_attributeTypes[$attrCodes] !== 'multiselect') {
                        if (is_scalar($attrValue)) {
                            if (!in_array($fieldName, $this->_getExportMainAttrCodes())) {
                                $additionalAttributes[$fieldName] = $fieldName .
                                    ImportProduct::PAIR_NAME_VALUE_SEPARATOR . $this->wrapValue($attrValue);
                                if ($this->checkDivideofAttributes()) {
                                    $addtionalFields[$fieldName] = $attrValue;
                                    if (!in_array($fieldName, $this->keysAdditional)) {
                                        $this->keysAdditional[] = $fieldName;
                                    }
                                }
                            }
                            $data[$itemId][$storeId][$fieldName] = htmlspecialchars_decode($attrValue);
                        }
                    } else {
                        $this->collectMultiselectValues($item, $attrCodes, $storeId);
                        if (!empty($this->collectedMultiselectsData[$storeId][$productLinkId][$attrCodes])) {
                            $additionalAttributes[$attrCodes] = $fieldName .
                                ImportProduct::PAIR_NAME_VALUE_SEPARATOR . implode(
                                    ImportProduct::PSEUDO_MULTI_LINE_SEPARATOR,
                                    $this->wrapValue(
                                        $this->collectedMultiselectsData[$storeId][$productLinkId][$attrCodes]
                                    )
                                );
                            if ($this->checkDivideofAttributes()) {
                                if (!in_array($attrCodes, $this->keysAdditional)) {
                                    $this->keysAdditional[] = $attrCodes;
                                }
                                $addtionalFields[$attrCodes] =
                                    $this->collectedMultiselectsData[$storeId][$productLinkId][$attrCodes];
                            }
                        }
                    }
                }
                if (!empty($additionalAttributes)) {
                    $additionalAttributes = array_map('htmlspecialchars_decode', $additionalAttributes);
                    $data[$itemId][$storeId][self::COL_ADDITIONAL_ATTRIBUTES] =
                        implode($this->multipleValueSeparator, $additionalAttributes);
                } else {
                    unset($data[$itemId][$storeId][self::COL_ADDITIONAL_ATTRIBUTES]);
                }

                if (!empty($data[$itemId][$storeId]) || $this->hasMultiselectData($item, $storeId)) {
                    $attrSetId = $item->getAttributeSetId();
                    $data[$itemId][$storeId][self::COL_STORE] = $storeCode;
                    $data[$itemId][$storeId][self::COL_ATTR_SET] = $this->_attrSetIdToName[$attrSetId];
                    $data[$itemId][$storeId][self::COL_TYPE] = $item->getTypeId();
                }
                if (!empty($addtionalFields)) {
                    foreach ($addtionalFields as $key => $value) {
                        $data[$itemId][$storeId][$key] = $value;
                    }
                }
                $data[$itemId][$storeId][self::COL_SKU] = htmlspecialchars_decode($item->getSku());
                $data[$itemId][$storeId]['status'] = $item->getStatus();
                $data[$itemId][$storeId]['product_online'] = $item->getStatus();
                $data[$itemId][$storeId]['store_id'] = $storeId;
                $data[$itemId][$storeId]['product_id'] = $itemId;
                $data[$itemId][$storeId]['product_link_id'] = $productLinkId;
            }
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    protected function collectMultiselectValues($item, $attrCode, $storeId)
    {
        $attrValue = $item->getData($attrCode);
        $optionIds = explode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $attrValue);
        $options = array_intersect_key(
            $this->_attributeValues[$attrCode],
            array_flip($optionIds)
        );
        $linkId = $item->getData($this->getProductEntityLinkField());
        if (!(isset($this->collectedMultiselectsData[Store::DEFAULT_STORE_ID][$linkId][$attrCode])
            && $this->collectedMultiselectsData[Store::DEFAULT_STORE_ID][$linkId][$attrCode] == $options)
        ) {
            $this->collectedMultiselectsData[$storeId][$linkId][$attrCode] = $options;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    protected function optionRowToCellString($option)
    {
        $result = [];

        foreach ($option as $key => $value) {
            $result[] = $key . ImportProduct::PAIR_NAME_VALUE_SEPARATOR . $value;
        }

        return implode($this->multipleValueSeparator, $result);
    }

    private function wrapValue(
        $value
    ) {
        if (!empty($this->_parameters[Export::FIELDS_ENCLOSURE])) {
            $wrap = function ($value) {
                return sprintf('"%s"', str_replace('"', '""', $value));
            };

            $value = is_array($value) ? array_map($wrap, $value) : $wrap($value);
        }

        return $value;
    }

    /**
     * @param $stockItemRows
     */
    protected function setAddHeaderColumns($stockItemRows)
    {
        $addData = [];

        if (!empty($stockItemRows)) {
            if (reset($stockItemRows)) {
                $addData = array_keys(end($stockItemRows));
                foreach ($addData as $key => $value) {
                    if (is_numeric($value)) {
                        unset($addData[$key]);
                    }
                }
            }
        }
        if (!$this->_headerColumns) {
            $this->_headerColumns = array_merge(
                [
                    self::COL_SKU,
                    self::COL_STORE,
                    self::COL_ATTR_SET,
                    self::COL_TYPE,
                    self::COL_CATEGORY,
                    self::COL_CATEGORY . '_position',
                    self::COL_CATEGORY_IDS,
                    self::COL_PRODUCT_WEBSITES,
                ],
                $this->_getExportMainAttrCodes(),
                $addData,
                [
                    'related_skus',
                    'related_position',
                    'crosssell_skus',
                    'crosssell_position',
                    'upsell_skus',
                    'upsell_position',
                    'additional_images',
                    'additional_image_labels',
                    'hide_from_product_page',
                    'custom_options',
                ]
            );
            if (!$this->checkDivideofAttributes()) {
                $this->_headerColumns = array_merge(
                    $this->_headerColumns,
                    [
                        self::COL_ADDITIONAL_ATTRIBUTES
                    ]
                );
            }
        }
    }

    protected function addHeaderColumns()
    {
        if ($this->checkDivideofAttributes()) {
            $this->_headerColumns = array_merge($this->_headerColumns, $this->keysAdditional);
        }

        $this->_headerColumns = array_unique(array_merge(
            $this->_headerColumns,
            $this->_getExportAttrCodes()
        ));
    }

    /**
     * @return array
     */
    protected function fireloadCollection()
    {
        $data = [];
        /** @var ProductCollection $collection */
        $collection = $this->_getEntityCollection()->clear();

        if (isset($this->getParameters()['only_admin'])
            && $this->getParameters()['only_admin'] == 1
        ) {
            $collection->addAttributeToSelect('*');
            $collection->addStoreFilter(Store::DEFAULT_STORE_ID);
            /**
             * @var int $itemId
             * @var \Magento\Catalog\Model\Product $item
             */
            foreach ($collection as $itemId => $item) {
                $data[$itemId][Store::DEFAULT_STORE_ID] = $item;
            }
            $collection->clear();
        } else {
            $collectionByStore = clone $collection;
            foreach (array_keys($this->getStores()) as $storeId) {
                $collectionByStore->addStoreFilter($storeId);
                if ($this->getLastPageExportedStatus($storeId)) {
                    continue;
                }
                $this->setLastPageExportedStatus($collectionByStore, $storeId);
                foreach ($collectionByStore as $itemId => $item) {
                    $data[$itemId][$storeId] = $item;
                }
                $collectionByStore->clear();
            }
            unset($collectionByStore);
        }
        return $data;
    }

    /**
     * @param $storeId
     * @return bool
     */
    protected function getLastPageExportedStatus($storeId)
    {
        $result = false;
        if (isset($this->isLastPageExported[$storeId])) {
            $result = true;
        } else {
            $lastPageExportedByStores = $this->cache->load('last_page_exported');
            if ($lastPageExportedByStores) {
                $lastPageExportedByStores = $this->serializer->unserialize($lastPageExportedByStores);
                foreach ($lastPageExportedByStores as $key => $value) {
                    $this->isLastPageExported[$key] = $value;
                }
            }
            if (isset($lastPageExportedByStores[$storeId])) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * @param $collectionByStore
     * @param $storeId
     */
    protected function setLastPageExportedStatus($collectionByStore, $storeId)
    {
        $curPage = $collectionByStore->getCurPage();
        $lastPage = $collectionByStore->getLastPageNumber();

        if ($curPage == $lastPage) {
            $this->isLastPageExported[$storeId] = true;
            $isLastPageExportedSerial = $this->serializer->serialize($this->isLastPageExported);
            $this->cacheSave($isLastPageExportedSerial, 'last_page_exported');
        }
    }

    /**
     * @param $cacheData
     * @param $identifier
     * @return bool
     */
    protected function cacheSave($cacheData, $identifier)
    {
        return $this->cache->save(
            $cacheData,
            $identifier,
            [self::CACHE_TAG]
        );
    }

    /**
     * @return bool
     */
    private function getAllStoresExported()
    {
        $lastPageExportedByStores = $this->cache->load('last_page_exported');
        if ($lastPageExportedByStores) {
            $lastPageExportedByStores = $this->serializer->unserialize($lastPageExportedByStores);
        }
        $isAllStoresExported = true;
        foreach (array_keys($this->getStores()) as $storeId) {
            if (!isset($lastPageExportedByStores[$storeId])) {
                $isAllStoresExported = false;
            }
        }

        return $isAllStoresExported;
    }

    /**
     * @return bool
     */
    protected function checkDivideofAttributes()
    {
        return isset($this->_parameters[Processor::DIVIDED_ATTRIBUTES]) &&
            $this->_parameters[Processor::DIVIDED_ATTRIBUTES];
    }

    /**
     * Clear media gallery cache by each page of collection items loaded to not overuse ram
     */
    protected function clearMediaGalleryCache()
    {
        $this->mediaGalleryCache = [];
    }

    /**
     * Warm up media gallery cache
     *
     * @param array $productIds
     * @throws Zend_Db_Statement_Exception
     */
    protected function warmUpMediaGalleryCache($productIds)
    {
        $this->getMediaGallery($productIds);
    }

    /**
     * Prepare products media gallery
     *
     * @param int[] $productIds
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    protected function getMediaGallery(array $productIds)
    {
        if (empty($productIds)) {
            return [];
        }

        $idsToLoad = $productIds;
        if (!empty($this->mediaGalleryCache)) {
            // Compare cached products media and requested
            $diff = array_diff($productIds, array_keys($this->mediaGalleryCache));
            if (empty($diff)) {
                // Gallery data for requested product ids is in cache
                return $this->getCachedMediaByIds($productIds);
            } else {
                // Load only not cached entities data
                $idsToLoad = $diff;
            }
        }

        $productEntityJoinField = $this->getProductEntityLinkField();
        $select = $this->_connection->select()->from(
            ['mgvte' => $this->_resourceModel->getTableName('catalog_product_entity_media_gallery_value_to_entity')],
            [
                "mgvte.$productEntityJoinField",
                'mgvte.value_id'
            ]
        )->joinLeft(
            ['mg' => $this->_resourceModel->getTableName('catalog_product_entity_media_gallery')],
            '(mg.value_id = mgvte.value_id)',
            [
                'mg.attribute_id',
                'filename' => 'mg.value',
                'mg.media_type'
            ]
        )->joinLeft(
            ['mgv' => $this->_resourceModel->getTableName('catalog_product_entity_media_gallery_value')],
            "(mg.value_id = mgv.value_id) and (mgvte.$productEntityJoinField = mgv.$productEntityJoinField)",
            [
                'mgv.label',
                'mgv.position',
                'mgv.disabled',
                'mgv.store_id',
            ]
        )->joinLeft(
            ['ev' => $this->_resourceModel->getTableName('catalog_product_entity_varchar')],
            "(mgvte.$productEntityJoinField = ev.$productEntityJoinField) and (mg.value = ev.value)",
            []
        )->joinLeft(
            ['ea' => $this->_resourceModel->getTableName('eav_attribute')],
            "(ea.attribute_id = ev.attribute_id)",
            [
                'ea.frontend_label'
            ]
        )->where(
            "mgvte.$productEntityJoinField IN (?)",
            $idsToLoad
        );

        $stmt = $this->_connection->query($select);
        while ($mediaRow = $stmt->fetch()) {
            if ($mediaRow['media_type'] === ProductVideo::EXTERNAL_VIDEO) {
                continue;
            }

            // Save data to cache
            $this->mediaGalleryCache[$mediaRow[$productEntityJoinField]][] = [
                '_media_attribute_id' => $mediaRow['attribute_id'],
                '_media_image' => $mediaRow['filename'],
                '_media_label' => $mediaRow['label'],
                '_media_position' => $mediaRow['position'],
                '_media_is_disabled' => $mediaRow['disabled'],
                '_media_store_id' => $mediaRow['store_id'],
                '_media_frontend_label' => $mediaRow['frontend_label'],
            ];
        }

        return $this->getCachedMediaByIds($productIds);
    }

    /**
     * Fetch media data from cache
     *
     * @param array $productIds
     * @return array
     */
    protected function getCachedMediaByIds($productIds)
    {
        if (empty($productIds) || empty($this->mediaGalleryCache)) {
            return [];
        }

        $data = array_intersect_key($this->mediaGalleryCache, array_flip($productIds));
        return $data;
    }

    /**
     * @param array $dataRow
     * @param array $multiRawData
     * @return array|null
     * @throws Zend_Db_Statement_Exception
     */
    protected function appendMultirowData(&$dataRow, &$multiRawData)
    {
        $pId = $dataRow['product_id'];
        $productLinkId = $dataRow['product_link_id'];
        $storeId = $dataRow['store_id'];
        $sku = $dataRow[self::COL_SKU];

        unset($dataRow['product_link_id']);
        unset($dataRow['store_id']);
        unset($dataRow[self::COL_SKU]);
        unset($dataRow[self::COL_STORE]);

        $this->updateDataWithCategoryColumns($dataRow, $multiRawData['rowCategories'], $pId);
        $this->updateDataWithCategoryPositionColumns($dataRow, $multiRawData['rowCategoriesPosition'], $pId);
        $this->updateDataWithCategoryIdsColumns($dataRow, $multiRawData['rowCategoryIds'], $pId);

        if (!empty($multiRawData['rowWebsites'][$pId])) {
            $websiteCodes = [];
            foreach ($multiRawData['rowWebsites'][$pId] as $productWebsite) {
                $websiteCodes[] = $this->_websiteIdToCode[$productWebsite];
            }
            $dataRow[self::COL_PRODUCT_WEBSITES] =
                implode($this->multipleValueSeparator, $websiteCodes);
            $multiRawData['rowWebsites'][$pId] = [];
        }

        $multiRawData['mediaGalery'] = $this->getMediaGallery([$productLinkId]);
        if (!empty($multiRawData['mediaGalery'][$productLinkId])) {
            $additionalImages = $additionalLabels = [];
            $baseImages = $baseLabels = [];
            $additionalImageIsDisabled = [];
            foreach ($multiRawData['mediaGalery'][$productLinkId] as $mediaItem) {
                if ($mediaItem['_media_frontend_label'] == 'Base') {
                    $baseImages[] = $mediaItem['_media_image'];
                    $baseLabels[] = $mediaItem['_media_label'];
                } else {
                    $additionalImages[] = $mediaItem['_media_image'];
                    $additionalLabels[] = $mediaItem['_media_label'];
                }
                if ($mediaItem['_media_is_disabled'] == true && $mediaItem['_media_store_id'] == $storeId) {
                    $additionalImageIsDisabled[] = $mediaItem['_media_image'];
                }
            }

            $dataRow['base_image'] = implode(
                $this->multipleValueSeparator,
                array_filter(array_unique($baseImages))
            );
            $dataRow['base_image_labels'] = implode(
                $this->multipleValueSeparator,
                array_filter(array_unique($baseLabels))
            );
            $dataRow['additional_images'] = implode(
                $this->multipleValueSeparator,
                array_filter(array_unique($additionalImages))
            );
            $dataRow['additional_image_labels'] = implode(
                $this->multipleValueSeparator,
                array_filter(array_unique($additionalLabels))
            );
            $dataRow['hide_from_product_page'] = implode(
                $this->multipleValueSeparator,
                array_filter(array_unique($additionalImageIsDisabled))
            );
            $multiRawData['mediaGalery'][$productLinkId] = [];
        }
        foreach ($this->_linkTypeProvider->getLinkTypes() as $typeName => $linkId) {
            if (!empty($multiRawData['linksRows'][$productLinkId][$linkId])) {
                $colPrefix = $typeName . '_';
                $associations = [];
                foreach ($multiRawData['linksRows'][$productLinkId][$linkId] as $linkData) {
                    if ($linkData['default_qty'] !== null) {
                        $skuItem = $linkData['sku']
                            . ImportProduct::PAIR_NAME_VALUE_SEPARATOR
                            . $linkData['default_qty'];
                    } else {
                        $skuItem = $linkData['sku'];
                    }
                    $associations[$skuItem] = $linkData['position'];
                }
                $multiRawData['linksRows'][$productLinkId][$linkId] = [];
                asort($associations);
                $dataRow[$colPrefix . 'skus'] = implode(
                    $this->multipleValueSeparator,
                    array_keys($associations)
                );
                $dataRow[$colPrefix . 'position'] = implode(
                    $this->multipleValueSeparator,
                    array_values($associations)
                );
            }
        }
        $dataRow = $this->rowCustomizer->addData($dataRow, $pId);

        if (!empty($this->collectedMultiselectsData[$storeId][$productLinkId])) {
            foreach (array_keys($this->collectedMultiselectsData[$storeId][$productLinkId]) as $attrKey) {
                if (!empty($this->collectedMultiselectsData[$storeId][$productLinkId][$attrKey])) {
                    $dataRow[$attrKey] =
                        implode(
                            $this->multipleValueSeparator,
                            $this->collectedMultiselectsData[$storeId][$productLinkId][$attrKey]
                        );
                }
            }
        }

        if (!empty($multiRawData['customOptionsData'][$productLinkId][$storeId])) {
            $customOptionsRows =
                $multiRawData['customOptionsData'][$productLinkId][$storeId];
            $multiRawData['customOptionsData'][$productLinkId][$storeId] = [];
            $customOptions =
                implode(ImportProduct::PSEUDO_MULTI_LINE_SEPARATOR, $customOptionsRows);

            $dataRow = array_merge(
                $dataRow,
                ['custom_options' => $customOptions]
            );
        }

        if (empty($dataRow)) {
            return null;
        } elseif ($storeId != Store::DEFAULT_STORE_ID) {
            $dataRow[self::COL_STORE] = $this->_storeIdToCode[$storeId];
        }
        $dataRow[self::COL_SKU] = $sku;

        return $dataRow;
    }

    /**
     * @return array
     */
    public function getMediaAttributesMap()
    {
        return [
            'base_image' => 'image',
            'base_image_label' => 'image_label',
            'thumbnail_image' => 'thumbnail',
            'thumbnail_image_label' => 'thumbnail_label'
        ];
    }

    /**
     * @param array $parameters
     * @param array $attributes
     * @return array
     */
    public function addMediaAttributes(array $parameters, array $attributes)
    {
        foreach ($attributes as $parameter => $attribute) {
            if (array_search($parameter, $parameters) !== false) {
                $parameters[] = $attribute;
            }
        }
        return $parameters;
    }

    protected function _getExportAttrCodes()
    {
        if (null === self::$attrCodes) {
            $attrCodes = [];
            $parameters = $this->_parameters;

            if (isset($parameters[Processor::ALL_FIELDS]) && $parameters[Processor::ALL_FIELDS] &&
                isset($parameters[Processor::LIST_DATA]) && is_array($parameters[Processor::LIST_DATA])) {
                $attrCodes = array_merge(
                    $this->_permanentAttributes,
                    $this->addMediaAttributes($parameters[Processor::LIST_DATA], $this->getMediaAttributesMap())
                );
            } else {
                foreach ($this->filterAttributeCollection($this->getAttributeCollection()) as $attribute) {
                    $attrCodes[] = $attribute->getAttributeCode();
                }
            }

            self::$attrCodes = $attrCodes;
        }

        return self::$attrCodes;
    }

    /**
     * {@inheritDoc}
     */
    protected function updateDataWithCategoryColumns(&$dataRow, &$rowCategories, $productId)
    {
        if (!isset($rowCategories[$productId])) {
            return false;
        }
        $categories = [];
        foreach ($rowCategories[$productId] as $categoryId) {
            if (!isset($this->_rootCategories[$categoryId])) {
                continue;
            }
            $categoryPath = $this->_rootCategories[$categoryId];
            if (isset($this->_categories[$categoryId])) {
                $categoryPath .= '/' . $this->_categories[$categoryId];
            }
            $categories[] = $categoryPath;
        }
        $dataRow[self::COL_CATEGORY] = implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $categories);
        unset($rowCategories[$productId]);

        return true;
    }

    /**
     * @param $dataRow
     * @param $rowCategoriesPosition
     * @param $productId
     * @return bool
     */
    protected function updateDataWithCategoryPositionColumns(&$dataRow, &$rowCategoriesPosition, $productId)
    {
        if (!isset($rowCategoriesPosition[$productId])) {
            return false;
        }
        $positions = [];
        foreach ($rowCategoriesPosition[$productId] as $categoryId => $position) {
            if (!isset($this->_rootCategories[$categoryId])) {
                continue;
            }
            $categoryPath = $this->_rootCategories[$categoryId];
            if (isset($this->_categories[$categoryId])) {
                $categoryPath .= '/' . $this->_categories[$categoryId];
            }
            $positions[] = $categoryPath . ImportProduct::PAIR_NAME_VALUE_SEPARATOR . $position;
        }
        $dataRow[self::COL_CATEGORY . '_position'] = implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $positions);
        unset($rowCategoriesPosition[$productId]);

        return true;
    }

    /**
     * @param $dataRow
     * @param $rowCategoryIds
     * @param $productId
     * @return bool
     */
    protected function updateDataWithCategoryIdsColumns(&$dataRow, &$rowCategoryIds, $productId)
    {
        if (!isset($rowCategoryIds[$productId])) {
            return false;
        }

        $category_ids = [];
        foreach ($rowCategoryIds[$productId] as $categoryId) {
            if (isset($this->_categories[$categoryId])) {
                $category_ids[] = $categoryId;
            }
        }

        $dataRow[self::COL_CATEGORY_IDS] = implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $category_ids);
        unset($rowCategoryIds[$productId]);

        return true;
    }

    /**
     * Filter by stores
     *
     * @return array
     */
    protected function getStores()
    {
        if (null === $this->stores) {
            $this->stores = [];
            if (isset($this->_parameters[Processor::BEHAVIOR_DATA]['store_ids'])
                && is_array($this->_parameters[Processor::BEHAVIOR_DATA]['store_ids'])
                && !in_array('0', $this->_parameters[Processor::BEHAVIOR_DATA]['store_ids'])
            ) {
                $storeIds = $this->_parameters[Processor::BEHAVIOR_DATA]['store_ids'];
                foreach ($this->_storeIdToCode as $id => $code) {
                    if (in_array($id, $storeIds)) {
                        $this->stores[$id] = $code;
                    }
                }
            } else {
                $this->stores = $this->_storeIdToCode;
            }
        }

        return $this->stores;
    }

    /**
     * Returns Swatch option data for Attribute Option Ids
     *
     * @param array $optionIds
     * @param int $attributeId
     *
     * @return array
     */
    protected function getSwatchesByOptionsId($optionIds, $attributeId)
    {
        if (!isset($this->cachedSwatchOptions[$attributeId]) || empty($this->cachedSwatchOptions[$attributeId])) {
            $this->cachedSwatchOptions[$attributeId] = [];
            $swatchCollection = $this->swatchCollectionFactory->create();
            $swatchCollection->addFilterByOptionsIds($optionIds);
            foreach ($swatchCollection as $item) {
                $this->cachedSwatchOptions[$attributeId][$item['option_id']] = $item->getData();
            }
        }

        return $this->cachedSwatchOptions[$attributeId];
    }

    /**
     * @return array
     */
    protected function getStoreWithCodes()
    {
        $stores = [];
        foreach ($this->_storeManager->getStores() as $store) {
            $stores[$store->getId()] = $store->getCode();
        }
        return $stores;
    }

    /**
     * @param AbstractAttribute $attribute
     *
     * @return array
     */
    public function getAttributeOptions(AbstractAttribute $attribute)
    {
        $options = [];

        if ($attribute->usesSource()) {
            // should attribute has index (option value) instead of a label?
            $index = in_array($attribute->getAttributeCode(), $this->_indexValueAttributes) ? 'value' : 'label';
            $stores = $this->getStoreWithCodes();
            $stores[0] = 'admin'; // We add admin store here for backward compatibility

            foreach ($stores as $id => $code) {
                // only default (admin) store values used
                $attribute->setStoreId($id);

                try {
                    foreach ($attribute->getSource()->getAllOptions(false) as $option) {
                        foreach (is_array($option['value']) ? $option['value'] : [$option] as $innerOption) {
                            if (strlen($innerOption['value'])) {
                                // skip ' -- Please Select -- ' option
                                $options[$id][$innerOption['value']] = (string)$innerOption[$index];
                            }
                        }
                    }

                    if ($this->swatchesHelperData->isTextSwatch($attribute)) {
                        $swatchOptionsIds = array_keys($options[$id]);
                        $swatchOption = $this->getSwatchesByOptionsId($swatchOptionsIds, $attribute->getAttributeId());
                        foreach ($swatchOption as $optionId => $optionValue) {
                            if ($optionValue['value'] !== '') {
                                $options[$id][$optionId] = (string)$optionValue['value'];
                            }
                        }
                    }
                } catch (Exception $e) {
                    $this->addLogWriteln($e->getMessage(), $this->getOutput(), 'error');
                }
            }
        }

        return $options;
    }

    /**
     * Retrieve entity field for export
     *
     * @return array
     * @throws LocalizedException
     */
    public function getFieldsForExport()
    {
        $stockItemRows = $this->fieldsCatalogInventory();
        $this->setHeaderColumns(1, $stockItemRows);
        $this->_headerColumns = $this->rowCustomizer->addHeaderColumns($this->_headerColumns);
        $this->_headerColumns = array_merge($this->_headerColumns, [self::COL_CATEGORY . '_position']);
        $this->_headerColumns = array_merge($this->_headerColumns, [self::COL_CATEGORY_IDS]);

        $subOptions = [];
        if (isset($this->_attributeColFactory)) {
            $attributeCollection = $this->_attributeColFactory->create()->addVisibleFilter()
                ->setOrder('attribute_code', Collection::SORT_ORDER_ASC);
            /** @var \Magento\Eav\Model\Entity\Attribute $attribute */
            foreach ($attributeCollection as $attribute) {
                $subOptions[] = $attribute->getAttributeCode();
            }
            $this->_headerColumns = array_merge($this->_headerColumns, $subOptions);
        }

        sort($this->_headerColumns);
        return array_unique($this->_headerColumns);
    }

    /**
     * @return string
     */
    public function _getProductEntityLinkField()
    {
        return $this->getProductEntityLinkField();
    }
}
