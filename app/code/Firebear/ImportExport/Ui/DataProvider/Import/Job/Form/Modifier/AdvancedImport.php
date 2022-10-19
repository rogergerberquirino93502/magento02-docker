<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\DataProvider\Import\Job\Form\Modifier;

use Firebear\ImportExport\Model\Source\Config as SourceConfig;
use Firebear\ImportExport\Model\Source\Platform\Config as PlatformConfig;
use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\App\Cache\Type\Block;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\File\Size;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\UrlInterface;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Psr\Log\LoggerInterface;
use Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\System\SupplierAttributeValue;

/**
 * Data provider for advanced inventory form
 */
class AdvancedImport implements ModifierInterface
{
    /**
     * Object manager
     *
     * @var ObjectManagerInterface
     */
    protected $objectManager;
    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var SourceConfig
     */
    protected $sourceConfig;

    /**
     * @var PlatformConfig
     */
    protected $platformConfig;

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $backendUrl;

    /**
     * @var Size
     */
    protected $fileSize;

    /**
     * @var LocatorInterface
     */
    protected $locator;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var CacheInterface
     */
    private $cacheManager;

    private $categoriesTree;

    private $logger;

    protected $request;

    const CONTAINER_PREFIX = 'container_';

    /**#@+
     * Category tree cache id
     */
    const CATEGORY_TREE_ID = 'CATALOG_PRODUCT_CATEGORY_TREE';
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * AdvancedImport constructor.
     *
     * @param ArrayManager $arrayManager
     * @param \Magento\Backend\Model\UrlInterface $backendUrl
     * @param SourceConfig $sourceConfig
     * @param PlatformConfig $platformConfig
     * @param Size $fileSize
     * @param UrlInterface $urlBuilder
     * @param LocatorInterface $locator
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param ObjectManagerInterface $objectManager
     * @param SerializerInterface $serializer
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ArrayManager $arrayManager,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        SourceConfig $sourceConfig,
        PlatformConfig $platformConfig,
        Size $fileSize,
        UrlInterface $urlBuilder,
        LocatorInterface $locator,
        CategoryCollectionFactory $categoryCollectionFactory,
        LoggerInterface $logger,
        RequestInterface $request,
        ObjectManagerInterface $objectManager,
        SerializerInterface $serializer,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->arrayManager = $arrayManager;
        $this->sourceConfig = $sourceConfig;
        $this->platformConfig = $platformConfig;
        $this->backendUrl = $backendUrl;
        $this->fileSize = $fileSize;
        $this->urlBuilder = $urlBuilder;
        $this->locator = $locator;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->logger = $logger;
        $this->request = $request;
        $this->objectManager = $objectManager;
        $this->serializer = $serializer;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        return $this->prepareMeta($meta);
    }

    /**
     * @return array
     */
    protected function addFieldSource()
    {
        $maxImageSize = $this->fileSize->getMaxFileSize();
        $childrenArray = [];
        $generalConfig = [
            'componentType' => 'field',
            'component' => 'Firebear_ImportExport/js/form/import-dep-file',
            'formElement' => 'input',
            'dataType' => 'text',
            'source' => 'import',
            'valueUpdate' => 'afterkeydown'
        ];
        $sourceConfig = [];
        $platformForm = [];
        $platformConfig = $this->platformConfig->get();
        foreach ($platformConfig as $entityType => $platforms) {
            foreach ($platforms as $platformName => $data) {
                if (isset($data['source_fields']) && is_array($data['source_fields'])) {
                    $prefix = $entityType . '_' . $platformName;
                    if (empty($platformForm[$prefix])) {
                        $platformForm[$prefix] = $prefix;
                    }
                    $fields = [];
                    foreach ($data['source_fields'] as $field) {
                        $fields[$field['id']] = $field;
                    }
                    $sourceConfig[$entityType . '_' . $platformName] = [
                        'label' => '',
                        'model' => null,
                        'sort_order' => 5,
                        'depends' => '',
                        'api' => '',
                        'fields' => $fields,
                    ];
                }
            }
        }
        $types = $this->sourceConfig->get();
        foreach (array_merge_recursive($sourceConfig, $types) as $typeName => $type) {
            $sortOrder = 20;
            foreach ($type['fields'] as $name => $values) {
                $localConfig = [
                    'label' => $values['label'],
                    'dataScope' => $name,
                    'sortOrder' => $sortOrder,
                    'valuesForOptions' => [
                        $typeName => $typeName
                    ],
                    'platformForm' => $platformForm
                ];
                if (isset($values['componentType']) && ($values['componentType'])) {
                    $localConfig['componentType'] = $values['componentType'];
                }
                if (isset($values['component']) && ($values['component'])) {
                    $localConfig['component'] = $values['component'];
                }
                if (isset($values['template']) && ($values['template'])) {
                    $localConfig['template'] = $values['template'];
                }
                if (isset($values['required']) && $values['required'] == "true") {
                    $localConfig['validation'] = [
                        'required-entry' => true
                    ];
                }
                if (isset($values['validation'])) {
                    if (strpos($values['validation'], " ") !== false) {
                        $array = explode(" ", $values['validation']);
                    } else {
                        $array = [$values['validation']];
                    }
                    foreach ($array as $item) {
                        $localConfig['validation'][$item] = true;
                    }
                }
                if (isset($values['url']) && $values['url']) {
                    $localConfig['uploaderConfig'] = [
                        'url' => $this->backendUrl->getUrl($values['url'])
                    ];
                }
                if (isset($values['notice']) && $values['notice']) {
                    $localConfig['notice'] = __($values['notice']);
                }
                if (isset($values['value']) && $values['value']) {
                    $localConfig['value'] = __($values['value']);
                }
                if ($values['componentType'] == 'fileUploader') {
                    $localConfig['maxFileSize'] = $maxImageSize;
                }
                if (isset($values['formElement']) && ($values['formElement'])) {
                    $localConfig['formElement'] = $values['formElement'];
                }
                $sortOrder += 10;
                $config = array_merge($generalConfig, $localConfig);

                $childrenArray[$typeName . "_" . $name] = [
                    'arguments' => [
                        'data' => [
                            'config' => $config
                        ],
                    ]
                ];
                if (isset($values['options']) && ($values['options'])) {
                    $childrenArray[$typeName . '_' . $name]['arguments']['data']['options'] =
                        $this->objectManager->create($values['options']);
                }
                if (isset($values['source_options']) && ($values['source_options'])) {
                    $childrenArray[$typeName . '_' . $name]['arguments']['data']['source_options'] =
                        $this->objectManager->create($values['source_options']);
                }
            }
        }
        $childrenArray['type_file']['arguments']['data']['config']['platformForm'] = $platformForm;
        $childrenArray['import_source']['arguments']['data']['config']['platformForm'] = $platformForm;
        return $childrenArray;
    }

    /**
     * @param array $meta
     * @return array
     */
    private function prepareMeta(array $meta)
    {
        $meta['source'] = ['children' => $this->addFieldSource()];
        $meta = $this->createNewCategoryModal($meta);
        $meta = $this->customizeCategoriesField($meta);
        if ($supplierAttribute = $this->scopeConfig->getValue('firebear_importexport/general/supplier_code')) {
            $meta = $this->disableProductManufacture($meta, $supplierAttribute);
        }
        return $meta;
    }

    /**
     * @param array $meta
     * @param string $supplierAttribute
     * @return array
     */
    protected function disableProductManufacture(array $meta, string $supplierAttribute)
    {
        $meta = $this->arrayManager->set(
            'settings',
            $meta,
            [
                'children' => [
                    'product_supplier' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'componentType' => 'field',
                                    'dataType' => 'text',
                                    'label' => __('Select Supplier'),
                                    'formElement' => 'select',
                                    'source' => 'import',
                                    'options' => $this->objectManager->get(SupplierAttributeValue::class)
                                        ->toOptionArray($supplierAttribute),
                                    'sortOrder' => 71,
                                ],
                            ],
                        ]
                    ]
                ]
            ]
        );
        return $meta;
    }

    /**
     * Create slide-out panel for new category creation
     *
     * @param array $meta
     * @return array
     */
    protected function createNewCategoryModal(array $meta)
    {
        $value = [];
        $value['arguments'] = [
            'data' => [
                'config' => [
                    'isTemplate' => false,
                    'componentType' => 'modal',
                    'options' => ['title' => __('New Category'),],
                    'imports' => ['state' => '!index=create_category:responseStatus'],
                ],
            ],
        ];

        $value['children']['create_category']['arguments']['data']['config'] = [
            'label' => '',
            'componentType' => 'container',
            'component' => 'Magento_Ui/js/form/components/insert-form',
            'dataScope' => '',
            'update_url' => $this->urlBuilder->getUrl('mui/index/render'),
            'render_url' => $this->urlBuilder->getUrl(
                'mui/index/render_handle',
                ['handle' => 'catalog_category_create', 'buttons' => '1']
            ),
            'autoRender' => false,
            'ns' => 'new_category_form',
            'externalProvider' => 'new_category_form.new_category_form_data_source',
            'toolbarContainer' => '${ $.parentName }',
            'formSubmitType' => 'ajax',
        ];

        return $this->arrayManager->set(
            'create_category_modal',
            $meta,
            $value
        );
    }

    /**
     * Customize Categories field
     *
     * @param array $meta
     * @return array
     */
    protected function customizeCategoriesField(array $meta)
    {
        $meta = $this->arrayManager->set(
            'source_data_map_container_category',
            $meta,
            [
                'children' => [
                    'new_category_button' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'title' => __('New Category'),
                                    'formElement' => 'container',
                                    'additionalClasses' => 'admin__field-small',
                                    'componentType' => 'container',
                                    'component' => 'Magento_Ui/js/form/components/button',
                                    'template' => 'ui/form/components/button/container',
                                    'actions' => [
                                        [
                                            'targetName' => 'import_job_form.import_job_form.create_category_modal',
                                            'actionName' => 'toggleModal',
                                        ],
                                        [
                                            'targetName' =>
                                                'import_job_form.import_job_form.create_category_modal.create_category',
                                            'actionName' => 'render'
                                        ],
                                        [
                                            'targetName' =>
                                                'import_job_form.import_job_form.create_category_modal.create_category',
                                            'actionName' => 'resetForm'
                                        ]
                                    ],
                                    'additionalForGroup' => true,
                                    'provider' => false,
                                    'source' => 'product_details',
                                    'displayArea' => 'insideGroup',
                                    'sortOrder' => 20,
                                ],
                            ],
                        ]
                    ]
                ]
            ]
        );
        return $meta;
    }

    /**
     * Retrieve categories tree
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getCategoriesTree($filter = null)
    {
        $categoryTree = $this->getCacheManager()->load(self::CATEGORY_TREE_ID . '_' . $filter);
        if ($categoryTree) {
            return $this->serializer->unserialize($categoryTree);
        }
        if ($this->categoriesTree === null) {
            $storeId = $this->request->getParam('store');
            /** @var Collection $matchingCollection */
            $matchingCollection = $this->categoryCollectionFactory->create();

            $matchingCollection->addAttributeToSelect('path')
                ->addAttributeToFilter('entity_id', ['neq' => CategoryModel::TREE_ROOT_ID])
                ->setStoreId($storeId);

            $shownCategoryIds = [];

            /** @var CategoryModel $category */
            foreach ($matchingCollection as $category) {
                foreach (explode('/', $category->getPath()) as $parentId) {
                    $shownCategoryIds[$parentId] = 1;
                }
            }

            /** @var Collection $categoryCollection */
            $categoryCollection = $this->categoryCollectionFactory->create();

            $categoryCollection->addAttributeToFilter('entity_id', ['in' => array_keys($shownCategoryIds)])
                ->addAttributeToSelect(['name', 'is_active', 'parent_id'])
                ->setOrder('entity_id', 'ASC')
                ->setStoreId($storeId);

            $categoryPaths = [];
            foreach ($categoryCollection as $category) {
                if ($category->hasChildren()) {
                    $this->recursiveCategory($category->getChildrenCategories(), $categoryPaths);
                }
            }
        }

        $this->categoriesTree = [];
        foreach ($categoryPaths as $path) {
            $this->categoriesTree[] = ['value' => $path, 'label' => $path];
        }

        $this->getCacheManager()->save(
            $this->serializer->serialize($this->categoriesTree),
            self::CATEGORY_TREE_ID . '_' . $filter,
            [
                CategoryModel::CACHE_TAG,
                Block::CACHE_TAG
            ]
        );

        return $this->categoriesTree;
    }

    private function recursiveCategory($categoryChildren, &$categoryPaths = [])
    {
        foreach ($categoryChildren as $categoryChild) {
            $categoryPaths[] = $this->getCategoryPath($categoryChild);
        }
    }

    private function getCategoryPath($categoryChild)
    {
        $storeId = $this->request->getParam('store');

        /** @var Collection $collection */
        $collection = $this->categoryCollectionFactory->create();

        $collection->addAttributeToFilter('entity_id', $categoryChild->getId())
            ->addAttributeToSelect(['name', 'is_active', 'parent_id', 'path'])
            ->setStoreId($storeId);
        $categoryFullPath = '';
        foreach ($collection as $category) {
            $categoryPath = $category->getPath();
            $explodeCategoryPath = explode('/', $categoryPath);
            foreach ($explodeCategoryPath as $categoryId) {
                if ($categoryId == 1) {
                    continue;
                }
                $collectionPathCategory = $this->categoryCollectionFactory->create();
                $collectionPathCategory->addAttributeToFilter('entity_id', $categoryId)
                    ->addAttributeToSelect(['name', 'is_active', 'parent_id', 'path'])
                    ->setStoreId($storeId);
                foreach ($collectionPathCategory as $categoryPath) {
                    if (empty($categoryFullPath)) {
                        $categoryFullPath = $categoryPath->getName();
                    } else {
                        $categoryFullPath .= '/' . $categoryPath->getName();
                    }
                }
            }
        }

        return $categoryFullPath;
    }

    /**
     * Retrieve cache interface
     *
     * @return CacheInterface
     * @deprecated
     */
    private function getCacheManager()
    {
        if (!$this->cacheManager) {
            $this->cacheManager = ObjectManager::getInstance()
                ->get(CacheInterface::class);
        }
        return $this->cacheManager;
    }
}
