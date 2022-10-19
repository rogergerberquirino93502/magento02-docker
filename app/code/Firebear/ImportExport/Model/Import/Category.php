<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import;

use Firebear\ImportExport\Api\Data\SeparatorFormatterInterface;
use Firebear\ImportExport\Traits\Import\Entity as ImportTrait;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category as MagentoCategoryModel;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\CatalogImportExport\Model\Import\Product\CategoryProcessor;
use Magento\Cms\Model\Page\DomValidationState;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\StringUtils;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\ResourceModel\Helper;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class Category
 *
 * @package Firebear\ImportExport\Model\Import
 */
class Category extends AbstractEntity
{
    use ImportTrait;

    /**
     * Delimiter in category path.
     */
    const DELIMITER_CATEGORY = '/';

    const PSEUDO_MULTI_LINE_SEPARATOR = '|';

    const PAIR_NAME_VALUE_SEPARATOR = '=';

    /**
     * Column category url key.
     */
    const COL_URL = 'url_key';

    const COL_STORE = 'store_view';

    const COL_STORE_NAME = 'store_name';

    const COL_URL_PATH = 'url_path';

    /**
     * Column category name.
     */
    const COL_NAME = 'name';

    /**
     * Column category parent id.
     */
    const COL_PARENT = 'parent_id';

    /**
     * Column category path.
     */
    const COL_PATH = 'path';

    /**
     * Column is active.
     */
    const COL_IS_ACTIVE = 'is_active';

    /**
     * Column Include in Menu.
     */
    const COL_INCLUDE_IN_MENU = 'include_in_menu';

    /**
     * Column Custom layout update.
     */
    const COL_CUSTOM_LAYOUT_UPDATE = 'custom_layout_update';

    /**
     * Column is anchor.
     */
    const COL_IS_ANCHOR = 'is_anchor';

    /**
     * Error codes
     */
    const ERROR_CODE_NAME_REQUIRED = 'columnNameIsRequired';
    const ERROR_CODE_LAYOUT_UPDATE_IS_NOT_VALID = 'CustomLayoutIsNotValid';
    const ERROR_ABSENT_REQUIRED_ATTRIBUTE = 'absentRequiredAttribute';

    protected $errorTemplates = [
        self::ERROR_CODE_NAME_REQUIRED => "Column 'name' is not set",
        self::ERROR_CODE_LAYOUT_UPDATE_IS_NOT_VALID => "Column 'custom_layout_update' is not valid",
        self::ERROR_ABSENT_REQUIRED_ATTRIBUTE => "Attribute %s is required",
    ];

    /**
     * Core event manager proxy
     *
     * @var ManagerInterface
     */
    protected $eventManager = null;

    /**
     * Flag for replace operation.
     *
     * @var null
     */
    protected $replaceFlag = null;

    /**
     * @var CategoryProcessor
     */
    protected $categoryProcessor;

    /**
     * @var CollectionFactory
     */
    protected $categoryColFactory;

    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;

    protected $storeManager;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    protected $resource;

    protected $resourceFactory;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * Categories text-path to ID hash.
     *
     * @var array
     */
    protected $categories = [];

    /**
     * @var array
     */
    protected $categoriesCache = [];

    protected $categoriesUrl;

    /**
     * @var \Magento\Framework\Filter\FilterManager
     */
    protected $filterManager;

    /**
     * @var DomValidationState
     */
    private $validationState;

    private $multiLineSeparatorForRegexp;

    protected $attributeCache = [];

    protected $attrData = [];

    protected $attributeCol;

    protected $sourceType;

    protected $nameToId;

    /**
     * @var \Magento\Framework\App\ProductMetadata
     */
    public $productMetadata;

    /**
     * @var \Firebear\ImportExport\Helper\Additional
     */
    protected $additional;

    /**
     * @var \Magento\Framework\View\Model\Layout\Update\ValidatorFactory
     */
    protected $validatorFactory;

    /**
     * @var array
     */
    protected $customAttr = [
        'custom_apply_to_products',
        'custom_design',
        'custom_design_from',
        'custom_design_to',
        'custom_layout_update',
        'custom_use_parent_settings',
        'description'
    ];

    /**
     * @var array
     */
    protected $categoriesDeleted = [];

    /**
     * @var array
     */
    protected $urlComparableList = [];

    /**
     * @var array
     */
    protected $urlRequestPathStoreId = [];

    /**
     * @var array
     */
    protected $foundDuplicate = [];

    /**
     * @var array
     */
    protected $noRequiredAttribute = [
        'available_sort_by',
        'default_sort_by',
        self::COL_IS_ACTIVE,
        self::COL_INCLUDE_IN_MENU
    ];

    /**
     * @var SeparatorFormatterInterface
     */
    private $separatorFormatter;

    /**
     * @param Data $jsonHelper
     * @param \Magento\ImportExport\Helper\Data $importExportData
     * @param \Magento\ImportExport\Model\ResourceModel\Import\Data $importData
     * @param Config $config
     * @param ResourceConnection $resource
     * @param Helper $resourceHelper
     * @param StringUtils $string
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param CollectionFactory $categoryColFactory
     * @param CategoryProcessor $categoryProcessor
     * @param CategoryFactory $categoryFactory
     * @param ManagerInterface $eventManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ConsoleOutput $output
     * @param \Magento\Framework\Registry $registry
     * @param \Firebear\ImportExport\Model\ResourceModel\Import\Data $importFireData
     * @param \Magento\Catalog\Model\ResourceModel\Category\Attribute\CollectionFactory $attributeColFactory
     * @param \Magento\Catalog\Model\ResourceModel\CategoryFactory $categoryResourceFactory
     * @param \Firebear\ImportExport\Helper\Additional $additional
     * @param \Magento\Framework\App\ProductMetadata $productMetadata
     * @param \Magento\Framework\View\Model\Layout\Update\ValidatorFactory $validatorFactory
     * @param \Magento\Framework\Filter\FilterManager $filterManager
     * @param SeparatorFormatterInterface $separatorFormatter
     * @param DomValidationState $validationState
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        Data $jsonHelper,
        \Magento\ImportExport\Helper\Data $importExportData,
        \Magento\ImportExport\Model\ResourceModel\Import\Data $importData,
        Config $config,
        ResourceConnection $resource,
        Helper $resourceHelper,
        StringUtils $string,
        ProcessingErrorAggregatorInterface $errorAggregator,
        CollectionFactory $categoryColFactory,
        CategoryProcessor $categoryProcessor,
        CategoryFactory $categoryFactory,
        ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        CategoryRepositoryInterface $categoryRepository,
        ConsoleOutput $output,
        Registry $registry,
        \Firebear\ImportExport\Model\ResourceModel\Import\Data $importFireData,
        \Magento\Catalog\Model\ResourceModel\Category\Attribute\CollectionFactory $attributeColFactory,
        \Magento\Catalog\Model\ResourceModel\CategoryFactory $categoryResourceFactory,
        \Firebear\ImportExport\Helper\Additional $additional,
        \Magento\Framework\App\ProductMetadata $productMetadata,
        \Magento\Framework\View\Model\Layout\Update\ValidatorFactory $validatorFactory,
        \Magento\Framework\Filter\FilterManager $filterManager,
        SeparatorFormatterInterface $separatorFormatter,
        $validationState = null
    ) {
        $this->categoryColFactory = $categoryColFactory;
        $this->categoryProcessor = $categoryProcessor;
        $this->categoryFactory = $categoryFactory;
        $this->categoryRepository = $categoryRepository;
        $this->eventManager = $eventManager;
        $this->registry = $registry;
        $this->storeManager = $storeManager;
        $this->output = $output;
        $this->attributeCol = $attributeColFactory;
        $this->resourceFactory = $categoryResourceFactory;
        $this->additional = $additional;
        $this->productMetadata = $productMetadata;
        $this->validatorFactory = $validatorFactory;
        $this->validationState = $validationState;
        $this->separatorFormatter = $separatorFormatter;

        if (version_compare($this->productMetadata->getVersion(), '2.2.2', '>=') && !$validationState) {
            $this->validationState = ObjectManager::getInstance()->get(
                DomValidationState::class
            );
        }
        parent::__construct(
            $jsonHelper,
            $importExportData,
            $importData,
            $config,
            $resource,
            $resourceHelper,
            $string,
            $errorAggregator
        );
        $this->_dataSourceModel = $importFireData;
        $this->initCategories()->initAttributes();
        $this->initRequestPathStoreId();
        $this->filterManager = $filterManager;
        $this->initErrorTemplates();
    }

    protected function initErrorTemplates()
    {
        /**
         * Add templates here because we rewrite aggregator in General Trait
         */
        foreach ($this->errorTemplates as $errorCode => $message) {
            $this->errorAggregator
                ->addErrorMessageTemplate($errorCode, $message);
        }
    }

    /**
     * Prepare all existing categories in array
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function initCategories()
    {
        if (empty($this->categories)) {
            $stores = $this->storeManager->getStores();
            $searchStores = [\Magento\Store\Model\Store::DEFAULT_STORE_ID];
            $this->nameToId['admin'] = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
            foreach ($stores as $store) {
                $this->nameToId[$store->getCode()] = $store->getId();
                $searchStores[] = $store->getId();
            }
            foreach ($searchStores as $store) {
                $collection = $this->categoryColFactory->create();
                $collection->setStoreId($store)
                    ->addAttributeToSelect(self::COL_NAME)
                    ->addAttributeToSelect(self::COL_URL);
                /** @var \Magento\Catalog\Model\Category $category */
                foreach ($collection as $category) {
                    $structure = explode(self::DELIMITER_CATEGORY, $category->getPath());
                    $pathSize = count($structure);
                    $this->categoriesCache[$category->getId()] = $category;

                    if ($pathSize > 1) {
                        $path = [];
                        for ($i = 1; $i < $pathSize; $i++) {
                            $path[] = $collection->getItemById((int)$structure[$i])->getName();
                        }
                        $index = implode(self::DELIMITER_CATEGORY, $path);
                        $this->categories[$index] = $category->getId();
                    } else {
                        $this->categories[$category->getName()] = $category->getId();
                    }
                }
            }
        }

        $this->setupKeyUrls();

        return $this;
    }

    protected function initAttributes()
    {
        foreach ($this->attributeCol->create() as $item) {
            $this->attrData[$item->getAttributeCOde()] = $item->getData();
        }
    }

    protected function searchInCategories($id, $data)
    {
        $array = [];
        foreach ($data as $el) {
            if ($el['entity_id'] == $id) {
                $array[$el['value']];
            }
        }

        return $array;
    }

    /**
     * Create Category entity from raw data.
     *
     * @throws \Exception
     * @return bool Result of operation.
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _importData()
    {
        $this->_validatedRows = null;
        if (Import::BEHAVIOR_DELETE == $this->getBehavior()) {
            $this->deleteCategories();
        } elseif (Import::BEHAVIOR_REPLACE == $this->getBehavior()) {
            $this->replaceProcess();
        } else {
            $this->saveCategoriesData();
        }
        $this->eventManager->dispatch('catalog_category_import_finish_before', ['adapter' => $this]);

        return true;
    }

    /**
     * Delete categories is delete behavior is selected
     * @return $this
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function deleteCategories()
    {
        $categoryId = null;
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $this->categoriesCache = [];
            foreach ($bunch as $rowNum => $rowData) {
                if (!$this->validateRow($rowData, $rowNum)) {
                    continue;
                }
                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                    continue;
                }
                if (isset($rowData['name']) && isset($this->categories[$rowData['name']])) {
                    $categoryId = (int)$this->categories[$rowData['name']];
                } elseif (isset($rowData['entity_id'])) {
                    $categoryId = (int)$rowData['entity_id'];
                }

                if ($categoryId) {
                    if ($this->categoryFactory->create()->
                    getCollection()->addFieldToFilter('entity_id', $categoryId)
                        ->getSize()) {
                        try {
                            $category = $this->categoryRepository->get($categoryId);
                            if ($this->getResource()->isForbiddenToDelete($categoryId)) {
                                $this->addRowError(
                                    'Cannot delete category ',
                                    $rowNum
                                );
                            } else {
                                $this->categoryRepository->delete($category);
                                if (Import::BEHAVIOR_REPLACE == $this->getBehavior()) {
                                    if (isset($rowData['name'])) {
                                        $this->categoriesDeleted[] = $rowData['name'];
                                    }
                                }
                            }
                        } catch (\Magento\Framework\Exception\StateException $e) {
                            $this->addRowError(
                                $e->getMessage(),
                                $rowNum
                            );
                        }
                    }
                } else {
                    $this->addRowError(
                        'Cannot delete category ',
                        $rowNum
                    );
                }
            }
        }

        return $this;
    }

    /**
     * Delete all categories when replace behavior is selected
     * @return $this
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function deleteAllCategories()
    {
        $this->deleteCategories();

        /**
         * Clear categories cache.
         */
        $this->categories = [];
        $this->categoriesCache = [];

        /**
         * Re-init default categories.
         */
        $this->initCategories();

        return $this;
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function replaceProcess()
    {
        $this->deleteAllCategories();
        $this->saveCategoriesData();

        return $this;
    }

    /**
     * @param array $rowData
     * @return array
     */
    protected function useOnlyFieldsFromMapping($rowData = [])
    {
        if (empty($this->_parameters['map'])) {
            return $rowData;
        }
        $requiredFields = ['include_in_menu' => 'Yes', 'is_active' => 'Yes'];
        $rowDataAfterMapping = [];
        foreach ($this->_parameters['map'] as $parameter) {
            if (array_key_exists($parameter['import'], $rowData)) {
                $rowDataAfterMapping[$parameter['system']] = $rowData[$parameter['import']];
            }
        }
        foreach ($requiredFields as $k => $value) {
            $rowDataAfterMapping[$k] = !empty($rowData[$k]) ? $rowData[$k] : $value;
        }
        if (empty($rowDataAfterMapping['name'])) {
            $this->addRowError(
                "Required field name is not mapped. Please, complete mapping and retry import.",
                $this->_processedRowsCount
            );
        }
        return $rowDataAfterMapping;
    }

    /**
     * Gather and save information about product entities.
     *
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function saveCategoriesData()
    {
        $this->_initSourceType('url');
        $groupCategoryId = [];
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $in = 0;
            $up = 0;
            $this->categoriesCache = [];
            $bunch = $this->prepareImagesFromSource($bunch);
            foreach ($bunch as $rowNum => $rowData) {
                if ($rowData['name'] == 'Root Catalog') {
                    continue;
                }
                $this->_processedRowsCount++;
                if (!empty($this->_parameters['use_only_fields_from_mapping'])) {
                    $rowData = $this->useOnlyFieldsFromMapping($rowData);
                    $nextBunch[$rowNum] = $rowData;
                }
                $rowData = $this->joinIdenticalyData($rowData);
                $rowData = $this->customChangeData($rowData);
                $rowData = $this->clearEmptyData($rowData, $rowNum);

                if (!$rowData) {
                    continue;
                }

                if (!isset($rowData[self::COL_NAME])) {
                    $this->getErrorAggregator()->addError(
                        self::ERROR_CODE_NAME_REQUIRED,
                        ProcessingError::ERROR_LEVEL_CRITICAL,
                        $this->_processedRowsCount
                    );
                    continue;
                }

                if (isset($rowData[self::COL_CUSTOM_LAYOUT_UPDATE])
                    && !empty($rowData[self::COL_CUSTOM_LAYOUT_UPDATE])) {
                    $rowData[self::COL_CUSTOM_LAYOUT_UPDATE] = stripslashes($rowData[self::COL_CUSTOM_LAYOUT_UPDATE]);
                    if (!$this->validateLayoutUpdateRow($rowData[self::COL_CUSTOM_LAYOUT_UPDATE])) {
                        $this->getErrorAggregator()->addError(
                            self::ERROR_CODE_LAYOUT_UPDATE_IS_NOT_VALID,
                            ProcessingError::ERROR_LEVEL_WARNING,
                            $this->_processedRowsCount
                        );
                        continue;
                    }
                }

                if (!$this->validateRow($rowData, $rowNum)) {
                    $this->addLogWriteln(
                        __('Category with name: %1 is not valided', $rowData[self::COL_NAME]),
                        $this->output,
                        'info'
                    );
                    continue;
                }
                $time = explode(" ", microtime());
                $startTime = $time[0] + $time[1];
                $name = $rowData[self::COL_NAME];

                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                    continue;
                }

                if (Import::BEHAVIOR_REPLACE == $this->getBehavior()) {
                    if (isset($rowData['entity_id'])) {
                        unset($rowData['entity_id']);
                    }
                    if (!empty($this->categoriesDeleted)) {
                        if (in_array($rowData[self::COL_NAME], $this->categoriesDeleted)) {
                            $rowData[self::COL_NAME];
                        } else {
                            continue;
                        }
                    }
                }

                $rowData = $this->changeData($rowData);
                $rowData['store_id'] = 0;
                if (!empty($rowData[self::COL_STORE])) {
                    if (isset($this->nameToId[$rowData[self::COL_STORE]])) {
                        $rowData['store_id'] = $this->nameToId[$rowData[self::COL_STORE]];
                        unset($rowData[self::COL_STORE]);
                    } else {
                        $this->addRowError(
                            "Store could not find for this category:".$rowData[self::COL_NAME],
                            $this->_processedRowsCount
                        );
                    }
                }
                $rowPath = $rowData[self::COL_NAME];
                if (!empty($rowPath)) {
                    if (is_int($rowPath)) {
                        try {
                            /** @var \Magento\Catalog\Model\Category $category */
                            $category = $this->categoryFactory->create();
                            if (!($parentCategory = isset($this->categoriesCache[$rowPath])
                                ? $this->categoriesCache[$rowPath] : null)
                            ) {
                                $parentCategory = $this->categoryFactory->create()->load($rowPath);
                            }

                            $category->setParentId($rowPath);
                            if (isset($rowData[self::COL_IS_ACTIVE])) {
                                $category->setIsActive($rowData[self::COL_IS_ACTIVE]);
                            }
                            if (isset($rowData[self::COL_INCLUDE_IN_MENU])) {
                                $category->setIncludeInMenu($rowData[self::COL_INCLUDE_IN_MENU]);
                            }

                            $category->setAttributeSetId($category->getDefaultAttributeSetId());
                            $category->setStoreId($rowData['store_id']);
                            $category->addData($rowData);

                            $category->setPath($parentCategory->getPath());
                            $category->save();
                            $this->categoriesCache[$category->getId()] = $category;
                            $in++;
                        } catch (\Exception $e) {
                            $this->getErrorAggregator()->addError(
                                $e->getCode(),
                                ProcessingError::ERROR_LEVEL_NOT_CRITICAL,
                                $this->_processedRowsCount,
                                null,
                                $e->getMessage()
                            );
                        }
                    } else {
                        $rowPathWithDefaultDelimiter = str_replace(
                            $this->_parameters['category_levels_separator'],
                            self::DELIMITER_CATEGORY,
                            $rowPath
                        );

                        if (isset($rowData['group']) && !empty($rowData['group'])) {
                            $catname = $rowData['_actual_name'];
                            $catid = '';
                            if (isset($groupCategoryId[$rowData['group']])
                                && !empty($groupCategoryId[$rowData['group']])) {
                                $catid = $groupCategoryId[$rowData['group']];
                            }
                            if (!$catid) {
                                $catid = isset($this->categories[$rowPath])
                                ? $this->categories[$rowPath] :'';
                            }

                            if ($catid) {
                                //update
                                ++$up;
                                $result = $this->updateCategoriesByPath($rowPathWithDefaultDelimiter, $rowData, $catid);
                                $result = true;
                                $groupCategoryId[$rowData['group']] = $catid;
                            } else {
                                //insert
                                ++$in;
                                $result = $this->prepareCategoriesByPath($rowPath, $rowData);
                                $groupCategoryId[$rowData['group']] = $result;
                            }
                        } elseif (isset($rowData['entity_id']) && !empty($rowData['entity_id'])) {
                              ++$up;
                            $result = $this->updateCategoriesByPath(
                                $rowPathWithDefaultDelimiter,
                                $rowData,
                                $rowData['entity_id']
                            );
                        } elseif (!isset($this->categories[$rowPathWithDefaultDelimiter])) {
                            ++$in;
                            $result = $this->prepareCategoriesByPath($rowPath, $rowData);
                        } else {
                            ++$up;
                            $result = $this->updateCategoriesByPath($rowPathWithDefaultDelimiter, $rowData);
                        }
                        if ($result === false) {
                            continue;
                        }
                    }
                }
                $time = explode(" ", microtime());
                $endTime = $time[0] + $time[1];
                $totalTime = $endTime - $startTime;
                $totalTime = round($totalTime, 5);
                $this->addLogWriteln(__('category with name: %1 .... %2s', $name, $totalTime), $this->output, 'info');
            }
            $this->addLogWriteln(__('Imported: %1 rows', $in), $this->output, 'info');
            $this->addLogWriteln(__('Updated: %1 rows', $up), $this->output, 'info');

            $this->eventManager->dispatch(
                'catalog_category_import_bunch_save_after',
                ['adapter' => $this, 'bunch' => $bunch]
            );
        }
        return $this;
    }

    /**
     * @param $rowData
     * @param $rownum
     * @return array
     */
    protected function clearEmptyData($rowData, $rownum)
    {
        foreach ($this->attrData as $attrDatum) {
            if (isset($rowData[$attrDatum['attribute_code']]) && $rowData[$attrDatum['attribute_code']] == '') {
                if ($attrDatum['is_required'] &&
                    !in_array($attrDatum['attribute_code'], $this->noRequiredAttribute)) {
                    $message = __('A required attribute missing %1', $attrDatum['attribute_code']);
                    $this->getErrorAggregator()->addError(
                        self::ERROR_CODE_NAME_REQUIRED,
                        ProcessingError::ERROR_LEVEL_CRITICAL,
                        $rownum,
                        $attrDatum['attribute_code'],
                        $message,
                        $message
                    );
                    return [];
                } else {
                    unset($rowData[$attrDatum['attribute_code']]);
                }
            }
        }
        return $rowData;
    }

    /**
     * Prepare new category by path.
     *
     * @param $rowPath
     * @param $rowData
     *
     * @return bool
     */
    protected function prepareCategoriesByPath($rowPath, $rowData)
    {
        $result = true;
        $parentId = MagentoCategoryModel::TREE_ROOT_ID;
        $pathParts = explode($this->_parameters['category_levels_separator'], $rowPath);
        $path = '';
        foreach ($pathParts as $pathPart) {
            if ($pathPart == '') {
                continue;
            }
            $path .= $pathPart;
            if (!isset($this->categories[$path])) {
                try {
                    $category = $this->categoryFactory->create();
                    if (!($parentCategory = isset($this->categoriesCache[$parentId])
                        ? $this->categoriesCache[$parentId] : null)
                    ) {
                        $parentCategory = $this->categoryFactory->create()->load($parentId);
                    }
                    $category->addData($rowData);
                    $category->setStoreId(0);
                    $category->setParentId($parentId);
                    $category->setIsActive(isset($rowData[self::COL_IS_ACTIVE]) ? $rowData[self::COL_IS_ACTIVE] : true);
                    $category->setIncludeInMenu(
                        isset($rowData[self::COL_INCLUDE_IN_MENU]) ? $rowData[self::COL_INCLUDE_IN_MENU] : true
                    );
                    $category->setAttributeSetId($category->getDefaultAttributeSetId());
                    $category->setName($pathPart);
                    if (isset($rowData[MagentoCategoryModel::KEY_AVAILABLE_SORT_BY])
                        && !empty($rowData[MagentoCategoryModel::KEY_AVAILABLE_SORT_BY])
                    ) {
                        $attrValue = \explode(
                            $this->getMultipleValueSeparator(),
                            $rowData[MagentoCategoryModel::KEY_AVAILABLE_SORT_BY]
                        );
                        $category->setAvailableSortBy($attrValue);
                    }
                    $category->setPath($parentCategory->getPath());
                    if ($category->getId()) {
                        $category->setPath($parentCategory->getPath() . self::DELIMITER_CATEGORY . $category->getId());
                        $category->isObjectNew(true);
                    }
                    $category->save();
                    $this->categoriesCache[$category->getId()] = $category;
                    $this->categories[$path] = $category->getId();
                    if (!empty($rowData[self::COL_STORE_NAME])) {
                        $this->updateCategoriesByPath($rowPath, $rowData);
                    }
                    $result = $category->getId();
                } catch (\Exception $e) {
                    $this->getErrorAggregator()->addError(
                        $e->getCode(),
                        ProcessingError::ERROR_LEVEL_NOT_CRITICAL,
                        $this->_processedRowsCount,
                        null,
                        $e->getMessage()
                    );
                    $result = false;
                }
            }
            if (isset($this->categories[$path])) {
                $parentId = $this->categories[$path];

                $path .= self::DELIMITER_CATEGORY;
            }
        }

        return $result;
    }

    /**
     * Update existing category by path.
     *
     * @param $rowPath
     * @param $rowData
     *
     * @return bool
     */
    protected function updateCategoriesByPath($rowPath, $rowData, $entityid = 0)
    {
        $result = true;
        if ($entityid) {
            $categoryId = $entityid;
        } else {
            $categoryId = $this->categories[$rowPath];
        }
        $category = $this->categoryFactory->create()->setStoreId($rowData['store_id'])->load($categoryId);
        if (!$category->getId()) {
            return $this->prepareCategoriesByPath($rowPath, $rowData);
        }
        $defaultCategory = $this->categoryFactory->create()->setStoreId(0)->load($categoryId);
        /**
         * Avoid changing category name and path
         */
        if (isset($rowData[self::COL_STORE_NAME]) && !empty($rowData[self::COL_STORE_NAME])) {
            $rowData[self::COL_NAME] = $rowData[self::COL_STORE_NAME];
            unset($rowData[self::COL_STORE_NAME]);
        } elseif (isset($rowData[self::COL_NAME])) {
            if ($categoryId) {
                //update store view category name so no need to add name
                $rowData[self::COL_NAME] = isset($rowData['_actual_name'])
                ? $rowData['_actual_name']
                : $rowData[self::COL_NAME];
                if ($rowData[self::COL_NAME] == $defaultCategory->getName()) {
                    if ($rowData['store_id'] != 0) {
                        $category->setName(null);
                    }
                }
            }
            unset($rowData[self::COL_NAME]);
        }
        if (isset($rowData[self::COL_STORE]) && empty($rowData[self::COL_STORE])) {
            unset($rowData[self::COL_STORE]);
        }
        if (isset($rowData[self::COL_PATH])) {
            unset($rowData[self::COL_PATH]);
        }
        try {
            foreach (\array_keys($this->attrData) as $attrCode) {
                if (!isset($rowData[$attrCode])) {
                    if ($category->getData($attrCode) == $defaultCategory->getData($attrCode)) {
                        if ($rowData['store_id'] != 0) {
                            $category->setData($attrCode, null);
                        }
                    }
                    continue;
                }
                if ($rowData[$attrCode] == $defaultCategory->getData($attrCode)) {
                    if ($rowData['store_id'] != 0 && $attrCode !== self::COL_IS_ANCHOR) {
                        $category->setData($attrCode, null);
                    }
                    continue;
                }
                if ($category->getData($attrCode) !== $rowData[$attrCode]
                ) {
                    if ($attrCode === MagentoCategoryModel::KEY_AVAILABLE_SORT_BY) {
                        $attrValue = \explode(
                            $this->getMultipleValueSeparator(),
                            $rowData[$attrCode]
                        );
                        $category->setData($attrCode, $attrValue);
                    } else {
                        $category->setData($attrCode, $rowData[$attrCode]);
                    }
                }
            }

            /**
             * set url_key in OrigData for method \Magento\Framework\Model\AbstractModel::dataHasChangedFor
             * cause url_path was change
             */
            if ((Import::BEHAVIOR_APPEND == $this->getBehavior()
                && $this->_parameters['generate_url'] == 1
                && isset($rowData['is_url_path_generated'])
                && $rowData['is_url_path_generated'] == 1)
                || (Import::BEHAVIOR_REPLACE == $this->getBehavior()
                && $this->_parameters['generate_url'] == 1
                && isset($rowData['is_url_path_generated'])
                && $rowData['is_url_path_generated'] == 1)
            ) {
                $urlCheck = $rowData['url_key'] . '1';
                $category->setOrigData('url_key', $urlCheck);
            }

            if (!$category->getUrlKey()) {
                $useDefault = $category->getData('use_default') ?: [];
                $useDefault['url_key'] = true;
                $category->setData('use_default', $useDefault);
            }
            $category->save();
        } catch (\Exception $e) {
            $this->getErrorAggregator()->addError(
                $e->getCode(),
                ProcessingError::ERROR_LEVEL_NOT_CRITICAL,
                $this->_processedRowsCount,
                null,
                $e->getMessage()
            );
            $result = false;
        }

        return $result;
    }

    /**
     * Validate data row.
     *
     * @param array $rowData
     * @param int $rowNum
     * @return boolean
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function validateRow(array $rowData, $rowNum)
    {
        if (!isset($rowData[self::COL_NAME])
            || empty($rowData[self::COL_NAME])
        ) {
            $this->getErrorAggregator()->addError(
                self::ERROR_CODE_NAME_REQUIRED,
                ProcessingError::ERROR_LEVEL_CRITICAL,
                $rowNum,
                self::COL_NAME
            );
        }

        if (isset($rowData[self::COL_CUSTOM_LAYOUT_UPDATE])
            && !empty($rowData[self::COL_CUSTOM_LAYOUT_UPDATE])) {
            $rowData[self::COL_CUSTOM_LAYOUT_UPDATE] = stripslashes($rowData[self::COL_CUSTOM_LAYOUT_UPDATE]);
            if (!$this->validateLayoutUpdateRow($rowData[self::COL_CUSTOM_LAYOUT_UPDATE])) {
                $this->getErrorAggregator()->addError(
                    self::ERROR_CODE_LAYOUT_UPDATE_IS_NOT_VALID,
                    ProcessingError::ERROR_LEVEL_WARNING,
                    $rowNum
                );
            }
        }

        /**
         * Check for required attributes if they are empty throw error
         */
        $entityId = !empty($rowData['entity_id']) ? (int)$rowData['entity_id'] : null;
        if (!$entityId || empty($this->categoriesCache[$entityId])) {
            foreach ($this->attrData as $attrCode => $attrDataNum) {
                if ($attrDataNum['is_required']
                    && !isset($rowData[$attrCode])
                    && empty($rowData[$attrCode])
                    && empty($rowData['entity_id'])
                ) {
                    /* @see self clearEmptyData method */
                    if (in_array($attrCode, $this->noRequiredAttribute)) {
                        continue;
                    }
                    $this->getErrorAggregator()
                        ->addError(
                            self::ERROR_ABSENT_REQUIRED_ATTRIBUTE,
                            ProcessingError::ERROR_LEVEL_CRITICAL,
                            $rowNum,
                            $attrCode
                        );
                }
            }
        }

        if (isset($this->_validatedRows[$rowNum])) {
            // check that row is already validated
            return !$this->getErrorAggregator()->isRowInvalid($rowNum);
        }
        $this->_validatedRows[$rowNum] = true;
        $this->_processedEntitiesCount++;

        return !$this->getErrorAggregator()->isRowInvalid($rowNum);
    }

    /**
     * Validate custom update row.
     *
     * @param string $validateString
     * @return boolean
     */
    protected function validateLayoutUpdateRow($validateString)
    {
        $layoutXmlValidator = $this->validatorFactory->create(
            [
                'validationState' => $this->validationState,
            ]
        );
        try {
            return $layoutXmlValidator->isValid($validateString);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * EAV entity type code getter.
     *
     * @abstract
     * @return string
     */
    public function getEntityTypeCode()
    {
        return 'catalog_category';
    }

    protected function setupKeyUrls()
    {
        $this->categoriesUrl = [];
        $collection = $this->categoryColFactory->create();
        $collection->addAttributeToSelect(self::COL_URL);
        foreach ($collection as $category) {
            $this->categoriesUrl[] = $category[self::COL_URL];
        }
    }

    /**
     * @return array
     */
    protected function getDataAttributes()
    {
        $category = $this->categoryFactory->create()->getResource();
        $attr = $category->getAttribute(self::COL_NAME);
        $attrName = $attr->getId();
        $table = $attr->getBackendTable();
        $entityTable = $category->getEntityTable();
        $collection = $this->categoryColFactory->create();
        $connection = $collection->getConnection();
        $indexList = $connection->getIndexList($entityTable);
        $entityIdField = $indexList[$connection->getPrimaryKeyName($entityTable)]['COLUMNS_LIST'][0];
        $stores = $this->storeManager->getStores();
        $searchStores = [\Magento\Store\Model\Store::DEFAULT_STORE_ID];
        foreach ($stores as $store) {
            $searchStores[] = $store->getId();
        }
        $select = $connection->select()->from(
            ['t_d' => $table],
            [$entityIdField, 'value']
        )
            ->where(
                't_d.attribute_id=?',
                $attrName
            )
            ->where(
                't_d.store_id IN(?)',
                $searchStores
            )
            ->where(
                't_d.store_id = ?',
                $connection->getIfNullSql('t_d.store_id', \Magento\Store\Model\Store::DEFAULT_STORE_ID)
            );

        return $this->_connection->fetchAll($select);
    }

    protected function _saveValidatedBunches()
    {
        $source = $this->_getSource();
        $currentDataSize = 0;
        $bunchRows = [];
        $startNewBunch = false;
        $nextRowBackup = [];
        $maxDataSize = $this->_resourceHelper->getMaxDataSize();
        $bunchSize = $this->_importExportData->getBunchSize();

        $source->rewind();
        $this->_dataSourceModel->cleanBunches();
        $file = null;
        $jobId = null;
        if (isset($this->_parameters['file'])) {
            $file = $this->_parameters['file'];
        }
        if (isset($this->_parameters['job_id'])) {
            $jobId = $this->_parameters['job_id'];
        }
        while ($source->valid() || $bunchRows) {
            if ($startNewBunch || !$source->valid()) {
                $this->addLogWriteln(
                    __('Saving Validated Bunches'),
                    $this->getOutput(),
                    'info'
                );
                $this->_dataSourceModel->saveBunches(
                    $this->getEntityTypeCode(),
                    $this->getBehavior(),
                    $jobId,
                    $file,
                    $bunchRows
                );
                $bunchRows = $nextRowBackup;
                $currentDataSize = strlen($this->phpSerialize($bunchRows));
                $startNewBunch = false;
                $nextRowBackup = [];
            }
            if ($source->valid()) {
                try {
                    $rowData = $source->current();
                } catch (\InvalidArgumentException $e) {
                    $this->addRowError($e->getMessage(), $this->_processedRowsCount);
                    $this->_processedRowsCount++;
                    $source->next();
                    continue;
                }

                $this->_processedRowsCount++;
                $rowData = $this->customBunchesData($rowData);
                $rowData = $this->customFieldsMapping($rowData, $source->key());

                if ($this->validateRow($rowData, $source->key())) {
                    $rowSize = strlen($this->jsonHelper->jsonEncode($rowData));

                    $isBunchSizeExceeded = $bunchSize > 0 && count($bunchRows) >= $bunchSize;

                    if ($currentDataSize + $rowSize >= $maxDataSize || $isBunchSizeExceeded) {
                        $startNewBunch = true;
                        $nextRowBackup = [$source->key() => $rowData];
                    } else {
                        $bunchRows[$source->key()] = $rowData;
                        $currentDataSize += $rowSize;
                    }
                }

                $source->next();
            }
        }

        return $this;
    }

    /**
     * @param $rowData
     * @param $rowNum
     * @return mixed
     */
    protected function findUrlKeyDuplicates($rowData, $rowNum)
    {
        $storeCode = isset($rowData[self::COL_STORE]) ? $rowData[self::COL_STORE] : 'default';
        $rowData = $this->urlPathSlashTrim($rowData);
        if (!isset($this->urlComparableList['url_path'][$storeCode])) {
            $this->urlComparableList['url_path'][$storeCode] = [];
        }
        if (!isset($this->urlComparableList['url_key'][$storeCode])) {
            $this->urlComparableList['url_key'][$storeCode] = [];
        }

        $rowData = $this->checkCategoryIfParentIsDuplicate($storeCode, $rowData, $rowNum);

        if (!empty($rowData[self::COL_URL_PATH])
            && count(explode('/', $rowData[self::COL_URL_PATH])) == 1
            && !empty($rowData[self::COL_URL])
        ) {
            if ($rowData[self::COL_URL] != $rowData[self::COL_URL_PATH]) {
                 $rowData[self::COL_URL_PATH] = $rowData[self::COL_URL];
            }
        }

        if (isset($rowData[self::COL_URL_PATH]) && isset($rowData[self::COL_URL])) {
            $rowData[self::COL_URL] = trim($rowData[self::COL_URL]);
            $rowData[self::COL_URL_PATH] = trim($rowData[self::COL_URL_PATH]);
        }

        if (isset($rowData[self::COL_URL_PATH]) && isset($rowData[self::COL_URL])
            && empty($rowData[self::COL_URL_PATH]) && empty($rowData[self::COL_URL])
        ) {
            $rowData = $this->getUrlKeyFromName($rowData);
            if ((isset($rowData[self::COL_URL]) && !empty($rowData[self::COL_URL])
                && in_array($rowData[self::COL_URL], $this->urlComparableList['url_key'][$storeCode]))
                || ($this->isUrlRequestPathDuplicateMage($rowData[self::COL_URL], $rowData))
            ) {
                $rowData = $this->generateUrlKeyOrError($rowData, $storeCode, $rowNum);
            } else {
                $this->urlComparableList['url_key'][$storeCode][] = $rowData[self::COL_URL] ?? '';
            }
        } else {
            if (isset($rowData[self::COL_URL_PATH]) && empty($rowData[self::COL_URL_PATH])) {
                if (($this->isRootCategory($rowData) && isset($rowData[self::COL_URL])
                    && !empty($rowData[self::COL_URL])
                    && in_array($rowData[self::COL_URL], $this->urlComparableList['url_key'][$storeCode]))
                    || ($this->isUrlRequestPathDuplicateMage($rowData[self::COL_URL_PATH], $rowData))
                ) {
                    $rowData = $this->generateUrlKeyOrError($rowData, $storeCode, $rowNum);
                } else {
                    $this->urlComparableList['url_key'][$storeCode][] = $rowData[self::COL_URL] ?? '';
                }
            } else {
                if (isset($rowData[self::COL_URL_PATH])) {
                    if ((isset($rowData[self::COL_URL_PATH]) && !empty($rowData[self::COL_URL_PATH])
                        && in_array($rowData[self::COL_URL_PATH], $this->urlComparableList['url_path'][$storeCode]))
                        || ($this->isUrlRequestPathDuplicateMage($rowData[self::COL_URL_PATH], $rowData))
                    ) {
                        $rowData = $this->generateUrlKeyOrError($rowData, $storeCode, $rowNum);
                    } else {
                        $this->urlComparableList['url_path'][$storeCode][] = $rowData[self::COL_URL_PATH] ?? '';
                    }
                }
            }
        }

        return $rowData;
    }

    /**
     * @param $rowData
     * @param $storeCode
     * @param $rowNum
     * @return mixed
     */
    protected function generateUrlKeyOrError($rowData, $storeCode, $rowNum)
    {
        $this->foundDuplicate[$storeCode][] = $rowData[self::COL_NAME];
        if ($this->_parameters['generate_url'] == 1) {
            $rowData = $this->generateUrlKeyProcess($rowData, $storeCode);
        } else {
            $message = 'category with name: %1 not imported because its url is not unique.';
            $this->addLogWriteln(__($message, $rowData[self::COL_NAME]), $this->output, 'error');
            $this->addRowError(__($message, $rowData[self::COL_NAME]), $rowNum);
        }
        return $rowData;
    }

    /**
     * @param $rowData
     * @return mixed
     */
    protected function getUrlKeyFromName($rowData)
    {
        if (isset($rowData[self::COL_NAME]) && !empty($rowData[self::COL_NAME])) {
            $separator = $this->_parameters['category_levels_separator'];
            $nameCategories = explode($separator, $rowData[self::COL_NAME]);
            $nameCategory = $nameCategories[count($nameCategories)-1];
            $nameCategory = preg_replace('/\s+/', '-', trim($nameCategory));
            $urlKey = mb_strtolower($nameCategory, 'UTF-8');
            $rowData[self::COL_URL] = $urlKey;
        }
        return $rowData;
    }

    /**
     * @param $rowData
     * @return mixed
     */
    protected function urlPathSlashTrim($rowData)
    {
        if (isset($rowData[self::COL_URL_PATH]) && !empty($rowData[self::COL_URL_PATH])) {
            if (substr($rowData[self::COL_URL_PATH], -1) == '/') {
                $rowData[self::COL_URL_PATH] = substr($rowData[self::COL_URL_PATH], 0, -1);
            }
        }
        return $rowData;
    }

    /**
     * @param $rowData
     * @return bool
     */
    protected function isRootCategory($rowData)
    {
        $result = false;
        if (isset($rowData[self::COL_PATH]) && !empty($rowData[self::COL_PATH])) {
            $countSlash = substr_count($rowData[self::COL_PATH], '/');
            if ($countSlash == 1) {
                $result = true;
            }
        } else {
            $name = isset($rowData[self::COL_NAME]) ? $rowData[self::COL_NAME] : '';
            if ($name == 'Root Catalog') {
                $result = false;
            } else {
                $countSlash = substr_count($name, '/');
                if ($countSlash == 0) {
                    $result = true;
                }
            }
        }
        return $result;
    }

    /**
     * @param $storeCode
     * @param $rowData
     * @param $rowNum
     * @return mixed
     */
    protected function checkCategoryIfParentIsDuplicate($storeCode, $rowData, $rowNum)
    {
        if (isset($this->foundDuplicate[$storeCode])) {
            foreach ($this->foundDuplicate[$storeCode] as $key => $categoryDuplicate) {
                $position = strpos($rowData[self::COL_NAME], $categoryDuplicate);
                if ($position === 0) {
                    if ($this->_parameters['generate_url'] == 1) {
                        $rowData['is_url_path_generated'] = 1;
                    } else {
                        $message = 'category with name: %1 not imported because its url is not unique.';
                        $this->addLogWriteln(__($message, $rowData[self::COL_NAME]), $this->output, 'error');
                        $this->addRowError(__($message, $rowData[self::COL_NAME]), $rowNum);
                    }
                    break;
                }
            }
        }
        return $rowData;
    }

    /**
     * @param $rowData
     * @param $storeCode
     * @return mixed
     */
    private function generateUrlKeyProcess($rowData, $storeCode)
    {
        if ($rowData[self::COL_NAME] != 'Root Catalog') {
            if (!empty($rowData[self::COL_URL_PATH])) {
                $oldUrl = $rowData[self::COL_URL_PATH];
            } else {
                $oldUrl = $rowData[self::COL_URL];
            }

            $i = 1;
            $newUrl = $this->generateUrl($oldUrl, $i);
            $generation = true;
            while ($generation) {
                if ($this->isUrlRequestPathDuplicateMage($newUrl, $rowData)) {
                    $i++;
                    $newUrl = $this->generateUrl($oldUrl, $i);
                } else {
                    if ($this->isUrlDuplicateComparableList($newUrl, $storeCode)) {
                        $i++;
                        $newUrl = $this->generateUrl($oldUrl, $i);
                    } else {
                        $this->addKeyInComparableList($newUrl, $storeCode, $rowData);
                        $rowData['is_url_path_generated'] = 1;
                        $generation = false;
                    }
                }
            }

            $rowData[self::COL_URL_PATH] = $newUrl;
            $newUrl=explode('/', $newUrl);
            $newUrlKey = $newUrl[count($newUrl)-1];
            $rowData[self::COL_URL] = $this->formatUrlKey($newUrlKey);
        }

        return $rowData;
    }

    /**
     * @param $url
     * @param $i
     * @return string
     */
    protected function generateUrl($url, $i)
    {
        return $url.$i;
    }

    /**
     * @param $url
     * @param $rowData
     * @return bool
     */
    protected function isUrlRequestPathDuplicateMage($url, $rowData)
    {
        $result = false;
        $storeId = $this->getStoreIdByCode($rowData);
        if (!empty($this->urlRequestPathStoreId)) {
            if (isset($this->urlRequestPathStoreId[$url][$storeId])
                || isset($this->urlRequestPathStoreId[$url . '.html'][$storeId])) {
                $entityId = !empty($rowData['entity_id']) ? $rowData['entity_id']
                    : $this->categories[$rowData[self::COL_NAME]] ?? '';
                if ((isset($rowData['entity_id']) || !empty($this->categories[self::COL_NAME]))
                    && (isset($this->urlRequestPathStoreId[$url . '.html'][$storeId]['entity_type']) == 'category'
                        && isset($this->urlRequestPathStoreId[$url . '.html'][$storeId]['entity_id']) == $entityId)
                    || (isset($this->urlRequestPathStoreId[$url][$storeId]['entity_type']) == 'category' &&
                        isset($this->urlRequestPathStoreId[$url][$storeId]['entity_id']) == $entityId)
                ) {
                    $result = false;
                } else {
                    $result = true;
                }
            }
        }
        return $result;
    }

    /**
     * @param $newUrl
     * @param $storeCode
     * @return bool
     */
    protected function isUrlDuplicateComparableList($newUrl, $storeCode)
    {
        $result = false;
        if (!empty($this->urlComparableList)) {
            if (in_array($newUrl, $this->urlComparableList['url_path'][$storeCode])
                || isset($this->urlComparableList['new_generated']['url_path'][$storeCode][$newUrl])
            ) {
                $result = true;
            } else {
                if (in_array($newUrl, $this->urlComparableList['url_key'][$storeCode])
                    || isset($this->urlComparableList['new_generated']['url_key'][$storeCode][$newUrl])
                ) {
                    $result = true;
                }
            }
        }
        return $result;
    }

    /**
     * @param $rowData
     * @return int
     */
    protected function getStoreIdByCode($rowData)
    {
        $storeId = 1;
        if (!empty($rowData[self::COL_STORE])) {
            if (isset($this->nameToId[$rowData[self::COL_STORE]])) {
                $storeId = $this->nameToId[$rowData[self::COL_STORE]];
            }
        }
        return $storeId;
    }

    /**
     * @return array
     */
    protected function initRequestPathStoreId()
    {
        $resource = $this->getResource();
        $select = $this->_connection->select()->from(
            ['url_rewrite' => $resource->getTable('url_rewrite')],
            ['request_path', 'store_id', 'entity_id', 'entity_type']
        );
        $requestPaths = $this->_connection->fetchAll(
            $select
        );
        if (!empty($requestPaths)) {
            foreach ($requestPaths as $key => $data) {
                $this->urlRequestPathStoreId[$data['request_path']][$data['store_id']] = [
                    'entity_id' => $data['entity_id'],
                    'entity_type' => $data['entity_type']
                ];
            }
        }
        return $this->urlRequestPathStoreId;
    }

    /**
     * @param $newUrl
     * @param $storeCode
     * @param $rowData
     */
    protected function addKeyInComparableList($newUrl, $storeCode, $rowData)
    {
        if (!empty($rowData[self::COL_URL_PATH])) {
            $this->urlComparableList['new_generated']['url_path'][$storeCode][$newUrl] = 1;
        } else {
            if (!empty($rowData[self::COL_URL])) {
                $this->urlComparableList['new_generated']['url_key'][$storeCode][$newUrl] = 1;
            }
        }
    }

    /**
     * @param $url
     * @param $rowData
     * @return bool
     */
    public function checkUrlKeyDuplicates($url, $rowData)
    {
        $result = false;
        $storeId = $this->getStoreIdByCode($rowData);

        $resource = $this->getResource();
        $select = $this->_connection->select()->from(
            ['url_rewrite' => $resource->getTable('url_rewrite')],
            ['request_path', 'store_id']
        )->joinLeft(
            ['cpe' => $resource->getTable('catalog_product_entity')],
            'cpe.entity_id = url_rewrite.entity_id'
        )->where('request_path LIKE "%' . $url . '"')
            ->orWhere('request_path LIKE "%' . $url . '.html"')
            ->where('store_id IN (?)', $storeId);
        $urlKeyDuplicates = $this->_connection->fetchAssoc(
            $select
        );
        if (!empty($urlKeyDuplicates)) {
            $result = true;
        }
        return $result;
    }

    protected function parseAdditionalAttributes($attributes)
    {
        return empty($this->_parameters[Import::FIELDS_ENCLOSURE])
            ? $this->parseAttributesWithoutWrappedValues($attributes)
            : $this->parseAttributesWithWrappedValues($attributes);
    }

    private function parseAttributesWithoutWrappedValues($data)
    {
        $attributeNameValuePairs = explode(
            $this->getMultipleValueSeparator(),
            $data
        );
        $result = [];
        $code = '';
        foreach ($attributeNameValuePairs as $attributeData) {
            //process case when attribute has ImportModel::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR inside its value
            if (strpos($attributeData, self::PAIR_NAME_VALUE_SEPARATOR) === false) {
                if (!$code) {
                    continue;
                }
                $result[$code] .= $this->getMultipleValueSeparator() . $attributeData;
                continue;
            }
            list($code, $value) = explode(
                self::PAIR_NAME_VALUE_SEPARATOR,
                $attributeData,
                2
            );
            $code = mb_strtolower($code);
            $result[$code] = $value;
        }
        return $result;
    }

    private function parseAttributesWithWrappedValues($data)
    {
        $attributesArray = [];
        preg_match_all(
            '~((?:[a-zA-Z0-9_])+)="((?:[^"]|""|"'
            . $this->getMultiLineSeparatorForRegexp()
            . '")+)"+~',
            $data,
            $matches
        );
        foreach ($matches[1] as $i => $attributeCode) {
            $attribute = $this
                ->retrieveAttributeByCode($attributeCode);
            $value = 'multiselect' != $attribute->getFrontendInput()
                ? str_replace('""', '"', $matches[2][$i])
                : '"' . $matches[2][$i] . '"';
            $attributesArray[mb_strtolower($attributeCode)] = $value;
        }
        return $attributesArray;
    }

    public function getMultipleValueSeparator()
    {
        $separator = Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR;
        if (!empty($this->_parameters[Import::FIELD_FIELD_MULTIPLE_VALUE_SEPARATOR])) {
            $separator = $this->_parameters[Import::FIELD_FIELD_MULTIPLE_VALUE_SEPARATOR];
        }
        return $this->separatorFormatter->format($separator);
    }

    private function getMultiLineSeparatorForRegexp()
    {
        if (!$this->multiLineSeparatorForRegexp) {
            $this->multiLineSeparatorForRegexp = in_array(self::PSEUDO_MULTI_LINE_SEPARATOR, str_split('[\^$.|?*+(){}'))
                ? '\\' . self::PSEUDO_MULTI_LINE_SEPARATOR
                : self::PSEUDO_MULTI_LINE_SEPARATOR;
        }
        return $this->multiLineSeparatorForRegexp;
    }

    /**
     * @param $rowData
     * @param $rowNum
     * @return mixed
     */
    public function customFieldsMapping($rowData, $rowNum)
    {
        if (isset($rowData[self::COL_NAME])) {
            $actualName = \explode(self::DELIMITER_CATEGORY, $rowData[self::COL_NAME]);
            $rowData['_actual_name'] = \end($actualName);
        }

        if (Import::BEHAVIOR_APPEND == $this->getBehavior()
            || Import::BEHAVIOR_REPLACE == $this->getBehavior()
        ) {
            $rowData = $this->findUrlKeyDuplicates($rowData, $rowNum);
        }

        return $rowData;
    }

    public function retrieveAttributeByCode($attrCode)
    {
        /** @var string $attrCode */
        $attrCode = mb_strtolower($attrCode);

        if (!isset($this->attributeCache[$attrCode])) {
            $this->attributeCache[$attrCode] = $this->getResource()->getAttribute($attrCode);
        }

        return $this->attributeCache[$attrCode];
    }

    public function changeData($rowData)
    {
        foreach ($this->customAttr as $value) {
            if (!array_key_exists($value, $rowData)) {
                continue;
            }
            $rowData[$value] = stripslashes($rowData[$value]);
        }

        foreach ($rowData as $key => $value) {
            if (isset($this->attrData[$key])) {
                $h = $this->attrData[$key];
                if ($h['frontend_input'] == 'select') {
                    $data = $this->retrieveAttributeByCode($key);
                    if (!empty($data->getOptions())) {
                        foreach ($data->getOptions() as $valueOptions) {
                            $valueData = $valueOptions->getData();
                            $valueLabel = $valueData['label'] ?? '';
                            if ($valueLabel instanceof \Magento\Framework\Phrase) {
                                $valueLabel = $valueLabel->getText();
                            }
                            if ($valueLabel == $value) {
                                $rowData[$key] = $valueData['value'];
                            }
                        }
                    }
                }
            }
        }

        return $rowData;
    }

    protected function getResource()
    {
        if (!$this->resource) {
            $this->resource = $this->resourceFactory->create();
        }
        return $this->resource;
    }

    protected function prepareImagesFromSource($bunch)
    {
        $image = 'image';

        foreach ($bunch as $rowNum => &$rowData) {
            if (empty($rowData[$image])) {
                continue;
            }
            $importImage = $rowData[$image];
            if (preg_match('/\bhttps?:\/\//i', $importImage, $matches)) {
                $urlStructure = explode('/', $importImage);
                $imageSting = mb_strtolower(array_pop($urlStructure));
                if ($this->sourceType) {
                    $this->sourceType->importImageCategory($importImage, $imageSting);
                }
                $rowData[$image] = $imageSting;
            }
        }

        return $bunch;
    }

    protected function _initSourceType($type)
    {
        if (!$this->sourceType) {
            $this->sourceType = $this->additional->getSourceModelByType($type);
            $this->sourceType->setData($this->_parameters);
        }
    }

    /**
     * Returns initial Categories set in initCategories() method
     *
     * @return array
     */
    public function getInitialCategories()
    {
        return $this->categories;
    }

    /**
     * Format URL key from name or defined key
     *
     * @param string $str
     * @return string
     */
    public function formatUrlKey($str)
    {
        return $this->filterManager->translitUrl($str);
    }

    /**
     * @param $categoryId
     * @return mixed|null
     */
    public function getCategoryById($categoryId)
    {
        return $this->categoriesCache[$categoryId] ?? null;
    }
}
