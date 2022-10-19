<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export;

use Exception;
use Firebear\ImportExport\Api\Data\SeparatorFormatterInterface;
use Firebear\ImportExport\Traits\Export\Entity as ExportTrait;
use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Catalog\Model\ResourceModel\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Type as EntityType;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\ImportExport\Model\Export\ConfigInterface;
use Magento\ImportExport\Model\Export\Entity\AbstractEntity;
use Magento\ImportExport\Model\Import;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;

/**
 * Class Category
 *
 * @package Firebear\ImportExport\Model\Export
 */
class Category extends AbstractEntity implements EntityInterface
{
    use ExportTrait;

    const COL_STORE = 'store_view';
    const COL_STORE_NAME = 'store_name';

    /**
     * @var string|null
     */
    protected $currentStoreCode = null;

    /**
     * @var Collection
     */
    protected $entityCollection;

    /**
     * @var CollectionFactory
     */
    protected $entityCollectionFactory;

    /**
     * @var ConfigInterface
     */
    protected $exportConfig;

    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\Attribute\CollectionFactory
     */
    protected $attributeColFactory;

    /**
     * Attribute types
     *
     * @var array
     */
    protected $attributeTypes = [];

    /**
     * Website ID-to-code.
     *
     * @var array
     */
    protected $websiteIdToCode = [];

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Type\CollectionFactory
     */
    protected $typeCollection;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory
     */
    protected $collectionAttr;

    private $userDefinedAttributes = [];

    protected $headerColumns = [];

    protected $fieldsMap = [];

    protected $dateAttrCodes = [
        'special_from_date',
        'special_to_date',
        'news_from_date',
        'news_to_date',
        'custom_design_from',
        'custom_design_to',
    ];

    protected $customAttr = [
        'custom_apply_to_products',
        'custom_design',
        'custom_design_from',
        'custom_design_to',
        'custom_layout_update',
        'custom_use_parent_settings',
        'description',
    ];

    protected $closedAttr = [
        'all_children',
        'children',
        'children_count',
        'level',
    ];

    /**
     * Items per page for collection limitation
     *
     * @var int|null
     */
    protected $itemsPerPage = null;

    /**
     * @var SeparatorFormatterInterface
     */
    private $separatorFormatter;

    /**
     * Category constructor.
     *
     * @param TimezoneInterface $localeDate
     * @param Config $config
     * @param ResourceConnection $resource
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $collectionFactory
     * @param ConfigInterface $exportConfig
     * @param CategoryFactory $categoryFactory
     * @param \Magento\Catalog\Model\ResourceModel\Category\Attribute\CollectionFactory $attributeColFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Type\CollectionFactory $typeCollection
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory $collectionAttrFactory
     * @param SeparatorFormatterInterface $separatorFormatter
     */
    public function __construct(
        TimezoneInterface $localeDate,
        Config $config,
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        CollectionFactory $collectionFactory,
        ConfigInterface $exportConfig,
        CategoryFactory $categoryFactory,
        \Magento\Catalog\Model\ResourceModel\Category\Attribute\CollectionFactory $attributeColFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Type\CollectionFactory $typeCollection,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory $collectionAttrFactory,
        SeparatorFormatterInterface $separatorFormatter
    ) {
        $this->entityCollectionFactory = $collectionFactory;
        $this->exportConfig = $exportConfig;
        $this->categoryFactory = $categoryFactory;
        $this->attributeColFactory = $attributeColFactory;
        $this->typeCollection = $typeCollection;
        $this->collectionAttr = $collectionAttrFactory;
        $this->separatorFormatter = $separatorFormatter;

        parent::__construct($localeDate, $config, $resource, $storeManager);

        $this->initAttributes()->initWebsites();
        $this->getFieldsForExport();
    }

    /**
     * Retrieve header columns
     *
     * @return array|string[]
     */
    public function _getHeaderColumns()
    {
        return $this->customHeadersMapping($this->headerColumns);
    }

    /**
     * @param array $rowData
     * @return array
     */
    protected function customHeadersMapping($rowData)
    {
        foreach ($rowData as $key => $fieldName) {
            if (isset($this->fieldsMap[$fieldName])) {
                $rowData[$key] = $this->fieldsMap[$fieldName];
            }
        }

        return ($this->_parameters['all_fields']) ? $this->headerColumns : array_unique($rowData);
    }

    /**
     * @param array $data
     */
    protected function setHeaderColumns($data)
    {
        if (!$this->headerColumns) {
            $this->headerColumns = array_merge(
                [
                    'entity_id',
                    'name',
                    self::COL_STORE,
                    self::COL_STORE_NAME,
                    'image',
                    'url_path',
                ],
                $data
            );
        }
    }

    /**
     * Retrieve entity field columns
     *
     * @return array
     * @throws LocalizedException
     */
    public function getFieldColumns()
    {
        $fields = [];
        foreach ($this->attributeTypes as $field => $type) {
            $option = [];
            foreach ($this->_attributeValues[$field] ?? [] as $value => $label) {
                $option[] = ['value' => $value, 'label' => $label];
            }
            $fields[] = [
                'field' => $field,
                'type' => $this->getAttributeType($type),
                'select' => $option
            ];
        }
        return [$this->getEntityTypeCode() => $fields];
    }

    /**
     * @param bool $resetCollection
     * @return Collection
     */
    protected function _getEntityCollection($resetCollection = false)
    {
        if ($resetCollection || empty($this->entityCollection)) {
            $this->entityCollection = $this->entityCollectionFactory->create();
        }

        return $this->entityCollection;
    }

    /**
     * @return array|Collection[]
     */
    protected function getArrEntityCollection()
    {
        $entityCollections = [];
        $stores = $this->_storeManager->getStores(true);
        $store_ids = $this->getStoreIdsForFilter();

        if ((empty($store_ids) && empty($this->_parameters['behavior_data']['store_ids']))
            || (empty($this->_parameters['only_admin']) && in_array('0', $store_ids))
        ) {
            foreach ($stores as $store) {
                $entity = $this->_getEntityCollection(true)->setStore($store);
                $entityCollections[$store->getCode()] = $entity;
            }
        } else {
            foreach ($store_ids as $storeId) {
                $store = $stores[$storeId];
                $entity = $this->_getEntityCollection(true)->setStore($store);
                $entityCollections[$store->getCode()] = $entity;
            }
        }

        return $entityCollections;
    }

    /**
     * @return int|null
     */
    protected function getItemsPerPage()
    {
        if ($this->itemsPerPage === null) {
            $memoryLimitConfigValue = trim(ini_get('memory_limit'));
            $lastMemoryLimitLetter = strtolower($memoryLimitConfigValue[strlen($memoryLimitConfigValue) - 1]);
            $memoryLimit = (int)$memoryLimitConfigValue;
            switch ($lastMemoryLimitLetter) {
                case 'g':
                    $memoryLimit *= 1024;
                //next
                case 'm':
                    $memoryLimit *= 1024;
                //next
                case 'k':
                    $memoryLimit *= 1024;
                    break;
                default:
                    $memoryLimit = 250000000;
            }

            $memoryPerProduct = 500000;
            $memoryUsagePercent = 0.8;
            $minProductsLimit = 500;
            $maxProductsLimit = 5000;

            $this->itemsPerPage = (int) (
                ($memoryLimit * $memoryUsagePercent - memory_get_usage(true)) / $memoryPerProduct
            );
            if ($this->itemsPerPage < $minProductsLimit) {
                $this->itemsPerPage = $minProductsLimit;
            }
            if ($this->itemsPerPage > $maxProductsLimit) {
                $this->itemsPerPage = $maxProductsLimit;
            }
        }

        return $this->itemsPerPage;
    }

    /**
     * Export process
     *
     * @return array
     * @throws LocalizedException
     */
    public function export()
    {
        //Execution time may be very long
        set_time_limit(0);

        $writer = $this->getWriter();
        $page = 0;
        $counts = 0;
        $storesRows = [];

        $storiesEntityCollection = $this->getArrEntityCollection();

        foreach ($storiesEntityCollection as $storeViewCode => $entityCollection) {
            $this->currentStoreCode=$storeViewCode;
            ++$page;
            $entityCollection->setOrder('entity_id', 'asc');
            $this->_prepareEntityCollection($entityCollection);
            if (isset($this->_parameters['last_entity_id'])
                && $this->_parameters['last_entity_id'] > 0
                && $this->_parameters['enable_last_entity_id'] > 0
            ) {
                $entityCollection->addFieldToFilter('entity_id', ['gt' => $this->_parameters['last_entity_id']]);
            }
            $entityCollection->setPage($page, $this->getItemsPerPage());

            if ($entityCollection->count() == 0) {
                break;
            }

            $exportData = $this->getExportData($entityCollection);
            if ($page == 1) {
                $writer->setHeaderCols($this->_getHeaderColumns());
            }
            foreach ($exportData as $dataRow) {
                if ($this->_parameters['enable_last_entity_id'] > 0) {
                    $this->lastEntityId = $dataRow['entity_id'];
                }
                $dd = $this->_customFieldsMapping($dataRow);
                $dd[self::COL_STORE] = $storeViewCode;
                $storesRows[$storeViewCode][] = $dd;
                $counts++;
            }
        }

        $newRows = $this->prepareRows($storesRows);
        foreach ($newRows as $line) {
            $writer->writeRow($line);
        }

        return [$writer->getContents(), $counts, $this->lastEntityId];
    }

    /**
     * @param array $storesRows
     * @return array
     */
    protected function prepareRows($storesRows)
    {
        $newRows = [];
        $firstStoreRows = array_shift($storesRows);
        if ($firstStoreRows) {
            foreach ($firstStoreRows as $numRow => $row) {
                $newRows[] = $row;
                if (!empty($storesRows)) {
                    foreach ($storesRows as $storeCode => $rows) {
                        if (isset($storesRows[$storeCode][$numRow])) {
                            $newRows[] = $storesRows[$storeCode][$numRow];
                        }
                    }
                }
            }
        }

        return $newRows;
    }

    /**
     * @param Collection $entityCollection
     * @return array
     */
    protected function getExportData($entityCollection)
    {
        $exportData = [];
        try {
            $rawData = $this->collectRawData($entityCollection);

            foreach ($rawData as $productId => $dataRow) {
                $exportData[] = $dataRow;
            }
        } catch (Exception $e) {
            $this->_logger->critical($e);
        }
        $newData = $this->changeData($exportData, 'entity_id');

        $this->headerColumns = $this->changeHeaders($this->headerColumns);

        return $newData;
    }

    /**
     * @param Collection $collection
     * @return array
     * @throws Exception
     */
    protected function collectRawData($collection)
    {
        $data = [];
        /**
         * @var int $itemId
         * @var CategoryModel $item
         */
        foreach ($collection as $itemId => $item) {
            $path = [];
            foreach ($this->getParentCategories($item) as $cat) {
                if ($cat->getId() == CategoryModel::TREE_ROOT_ID) {
                    continue;
                }
                $path[] = $cat->getName();
            }
            if (empty($path)) {
                $path[] = $item->getName();
            }
            $catName = implode("/", $path);
            $data[$itemId]['name'] = $catName;
            foreach ($this->_getExportAttrCodes() as $code) {
                if (strpos($catName, $item->getName()) !== false) {
                    $data[$itemId][self::COL_STORE_NAME] = $item->getName();
                }
                if ($code == 'name' || in_array($code, $this->closedAttr)) {
                    continue;
                }

                if ($code == 'default_sort_by') {
                    $attrValue = $item->getDefaultSortBy();
                } else {
                    $attrValue = $item->getData($code);
                }

                if (!$this->isValidAttributeValue($code, $attrValue)) {
                    continue;
                }
                if (isset($this->_attributeValues[$code][$attrValue]) && !empty($this->_attributeValues[$code])) {
                    $attrValue = $this->_attributeValues[$code][$attrValue];
                }
                $fieldName = isset($this->fieldsMap[$code]) ? $this->fieldsMap[$code] : $code;
                if ($this->attributeTypes[$code] == 'datetime') {
                    if (in_array($code, $this->dateAttrCodes) || in_array($code, $this->userDefinedAttributes)) {
                        $attrValue = $this->_localeDate->formatDateTime(
                            new \DateTime($attrValue),
                            \IntlDateFormatter::SHORT,
                            \IntlDateFormatter::NONE,
                            null,
                            date_default_timezone_get()
                        );
                    } else {
                        $attrValue = $this->_localeDate->formatDateTime(
                            new \DateTime($attrValue),
                            \IntlDateFormatter::SHORT,
                            \IntlDateFormatter::SHORT
                        );
                    }
                }

                if ($this->attributeTypes[$code] !== 'multiselect') {
                    if (is_scalar($attrValue)) {
                        if (in_array($fieldName, $this->customAttr)) {
                            $attrValue = addslashes($attrValue);
                        }
                        $data[$itemId][$fieldName] = htmlspecialchars_decode($attrValue);
                    }
                } else {
                    $data[$itemId][$fieldName] = $this->prepareMultiselectValue($attrValue);
                }
            }
            $data[$itemId]['image'] = $item->getImageUrl();
            $data[$itemId]['entity_id'] = $item->getEntityId();
        }

        return $data;
    }

    /**
     * Retrieve entity type code
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        return 'catalog_category';
    }

    /**
     * @return $this
     */
    protected function initAttributes()
    {
        foreach ($this->getAttributeCollection() as $attribute) {
            try {
                $this->_attributeValues[$attribute->getAttributeCode()] = $this->getAttributeOptions($attribute);
            } catch (\TypeError $exception) {
                // ignore exceptions connected with source models
                $this->_attributeValues[$attribute->getAttributeCode()] = [];
            }
            $this->attributeTypes[$attribute->getAttributeCode()] = Import::getAttributeType($attribute);
            if ($attribute->getIsUserDefined()) {
                $this->userDefinedAttributes[] = $attribute->getAttributeCode();
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function initWebsites()
    {
        /** @var Website $website */
        foreach ($this->_storeManager->getWebsites() as $website) {
            $this->websiteIdToCode[$website->getId()] = $website->getCode();
        }

        return $this;
    }

    /**
     * Retrieve entity field for export
     *
     * @return array
     */
    public function getFieldsForExport()
    {
        $list = [];
        foreach ($this->getAttributeCollection() as $attribute) {
            if (!in_array($attribute->getAttributeCode(), $this->closedAttr)) {
                $list[] = $attribute->getAttributeCode();
            }
        }
        $this->setHeaderColumns($list);

        return array_unique($this->headerColumns);
    }

    /**
     * Retrieve entity field for filter
     *
     * @return array
     */
    public function getFieldsForFilter()
    {
        $options = [];
        $types = $this->typeCollection->create()->addFieldToFilter('entity_type_code', $this->getEntityTypeCode());
        if ($types->getSize()) {
            /** @var EntityType $type */
            $type = $types->getFirstItem();
            $collection = $this->collectionAttr->create()->addFieldToFilter(
                'entity_type_id',
                $type->getId()
            );
            /** @var \Magento\Catalog\Model\Category\Attribute $item */
            foreach ($collection as $item) {
                $options[] = [
                    'value' => $item->getAttributeCode(),
                    'label' => $item->getDefaultFrontendLabel()
                        ? $item->getDefaultFrontendLabel()
                        : $item->getAttributeCode(),
                ];
            }
        }

        return [$this->getEntityTypeCode() => $options];
    }

    /**
     * Entity attributes collection getter.
     *
     * @return \Magento\Catalog\Model\ResourceModel\Category\Attribute\Collection
     */
    public function getAttributeCollection()
    {
        return $this->attributeColFactory->create();
    }

    /**
     * @param array $rowData
     * @return array
     */
    protected function _customFieldsMapping($rowData)
    {
        $headerColumns = $this->_getHeaderColumns();

        foreach ($this->fieldsMap as $systemFieldName => $fileFieldName) {
            if (isset($rowData[$systemFieldName])) {
                $rowData[$fileFieldName] = $rowData[$systemFieldName];
                unset($rowData[$systemFieldName]);
            }
        }
        if (count($headerColumns) != count(array_keys($rowData))) {
            $newData = [];
            foreach ($headerColumns as $code) {
                if (!isset($rowData[$code])) {
                    $newData[$code] = '';
                } else {
                    $newData[$code] = $rowData[$code];
                }
            }
            $rowData = $newData;
        }

        return $rowData;
    }

    /**
     * @param string $code
     * @param mixed $value
     * @return bool
     */
    protected function isValidAttributeValue($code, $value)
    {
        $isValid = true;
        if ((!is_numeric($value) && empty($value)) || !isset($this->_attributeValues[$code])) {
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * @param CategoryModel $category
     * @return array|DataObject[]
     * @throws LocalizedException
     */
    public function getParentCategories($category)
    {
        /** @var Store $store */
        $store = $this->_storeManager->getStore($this->currentStoreCode);
        if ($category->getId() > $store->getRootCategoryId()) {
            $path = implode(',', array_reverse($category->getPathIds()));
            $list = $path;
            $categories = array_reverse(explode(',', $list));
            /** @var Collection $categories */
            $collection = $this->entityCollectionFactory->create();
            /*Sort parent categories by level to get correct category path*/
            return $collection
                ->setStoreId($store->getId())
                ->addAttributeToSelect(
                    ['name', 'level']
                )->addFieldToFilter(
                    'entity_id',
                    ['in' => $categories]
                )->setOrder('level', 'ASC')->load()->getItems();
        }

        return [];
    }

    /**
     * @param string $value
     * @return string
     */
    private function prepareMultiselectValue($value)
    {
        $options = explode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $value);
        $separator = $this->separatorFormatter->format(
            $this->_parameters['behavior_data']['multiple_value_separator']
        );
        return implode($separator, $options);
    }
}
