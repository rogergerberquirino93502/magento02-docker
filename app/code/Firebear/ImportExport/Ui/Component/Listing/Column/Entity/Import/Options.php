<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Entity\Import;

use Firebear\ImportExport\Model\Import\Product;
use Firebear\ImportExport\Model\Source\Config\CartPrice;
use Firebear\ImportExport\Model\Source\Import\Config;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\ImportExport\Model\Import\Entity\Factory;

/**
 * Class Options
 */
class Options implements OptionSourceInterface
{

    const CATALOG_PRODUCT = 'catalog_product';

    const CATALOG_CATEGORY = 'catalog_category';

    /**
     * @var array
     */
    protected $options;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory
     */
    protected $attributeFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\Attribute\CollectionFactory
     */
    protected $attributeCategoryFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    protected $attributeCollection;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\Attribute\Collection
     */
    protected $attributeCategoryCollection;

    /**
     * @var Product
     */
    protected $productImportModel;

    protected $factory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var \Magento\ImportExport\Model\Import\Entity\Factory
     */
    protected $entityFactory;

    /**
     * @var \Firebear\ImportExport\Helper\Data
     */
    protected $helper;

    /**
     * @var \Firebear\ImportExport\Model\Source\Config\CartPrice
     */
    protected $cartPrice;

    protected $coreRegistry;

    protected $importConfig;

    /**
     * Options constructor.
     *
     * @param CollectionFactory $attributeFactory
     * @param Product $productImportModel
     */
    public function __construct(
        CollectionFactory $attributeFactory,
        \Magento\Catalog\Model\ResourceModel\Category\Attribute\CollectionFactory $attributeCategoryFactory,
        Config $config,
        Factory $entityFactory,
        CartPrice $cartPrice,
        \Firebear\ImportExport\Helper\Data $helper,
        \Magento\Framework\Registry $coreRegistry,
        \Firebear\ImportExport\Model\Source\Import\Config $importConfig
    ) {
        $this->attributeFactory = $attributeFactory;
        $this->attributeCategoryFactory = $attributeCategoryFactory;
        $this->config = $config;
        $this->entityFactory = $entityFactory;
        $this->cartPrice = $cartPrice;
        $this->helper = $helper;
        $this->coreRegistry = $coreRegistry;
        $this->importConfig = $importConfig;
    }

    /**
     * @param int $withoutGroup
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function toOptionArray($withoutGroup = 0, $entity = false)
    {
        $newOptions = [];

        if (!$entity) {
            $job = $this->coreRegistry->registry('import_job');
            if ($job->getId()) {
                $entity = $job->getEntity();
            } else {
                return [];
            }
        }

        foreach ($this->config->getEntities() as $key => $items) {
            if ($entity && $entity != $key) {
                continue;
            }
            if (in_array($key, [
                self::CATALOG_PRODUCT
            ])) {
                $newOptions[$key] = $this->getAttributeCatalog($withoutGroup);
            } elseif (in_array($key, [
                self::CATALOG_CATEGORY
            ])) {
                $newOptions[$key] = $this->getAttributeCategories($withoutGroup);
            } else {
                try {
                    $object = $this->entityFactory->create($items['model']);
                    $newOptions[$key] = $this->getAllFields($object);
                } catch (\Exception $e) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Please enter a correct entity model.')
                    );
                }
            }
        }

        $this->options = $newOptions;

        return $this->options;
    }

    /**
     * @return array
     */
    protected function getAttributeCatalog($withoutGroup = 0)
    {
        $attributeCollection = $this->getAttributeCollection();
        $options = [];
        $subOptions = [];

        foreach ($attributeCollection as $attribute) {
            $label = (!$withoutGroup) ?
                $attribute->getAttributeCode() . ' (' . $attribute->getFrontendLabel() . ')' :
                $attribute->getAttributeCode();
            $subOptions[] =
                [
                    'label' => $label,
                    'value' => $attribute->getAttributeCode()
                ];
        }
        unset($attributeCollection);
        if (!$withoutGroup) {
            $options[] = [
                'label' => __('Product Attributes'),
                'optgroup-name' => 'product_attributes',
                'value' => $subOptions
            ];
        } else {
            $options += $subOptions;
        }
        $specialAttributes = \Firebear\ImportExport\Model\Import\Product::$specialAttributes;
        $productTypes = $this->importConfig->getEntityTypes('catalog_product');
        foreach ($productTypes as $productTypeConfig) {
            $model = $productTypeConfig['model'];
            if (property_exists($model, 'specialAttributes')) {
                $specialAttributes = array_merge($specialAttributes, $model::$specialAttributes);
            }
        }
        $subOptions = [];
        foreach ($specialAttributes as $attribute) {
            $subOptions[] = ['label' => $attribute, 'value' => $attribute];
        }
        unset($specialAttributes);
        $AddFields = \Firebear\ImportExport\Model\Import\Product::$addFields;
        foreach ($AddFields as $attribute) {
            $subOptions[] = ['label' => $attribute, 'value' => $attribute];
        }
        unset($AddFields);
        if (!$withoutGroup) {
            $options[] = [
                'label' => __('Special Fields'),
                'optgroup-name' => 'special_attributes',
                'value' => $subOptions
            ];
        } else {
            $options = array_merge($options, $subOptions);
        }
        $subOptions = [];
        $subOptions[] = ['label' => '_category', 'value' => '_category'];
        $subOptions[] = ['label' => '_root_category', 'value' => '_root_category'];
        if (!$withoutGroup) {
            $options[] = [
                'label' => __('Other Fields'),
                'optgroup-name' => 'other_attributes',
                'value' => $subOptions
            ];
        } else {
            $options = array_merge($options, $subOptions);
        }

        return $options;
    }

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    public function getAttributeCollection()
    {
        $this->attributeCollection = $this->attributeFactory
            ->create()
            ->addVisibleFilter()
            ->setOrder('attribute_code', AbstractDb::SORT_ORDER_ASC);

        return $this->attributeCollection;
    }

    protected function getAttributeCategories($withoutGroup = 0)
    {
        $attributeCollection = $this->getAttributeCategoryCollection();
        $options = [];
        $subOptions = [];
        foreach ($attributeCollection as $attribute) {
            if ($attribute->getFrontendLabel()) {
                $label = (!$withoutGroup) ?
                    $attribute->getAttributeCode() . ' (' . $attribute->getFrontendLabel() . ')' :
                    $attribute->getAttributeCode();
                $subOptions[] =
                    [
                        'label' => $label,
                        'value' => $attribute->getAttributeCode()
                    ];
            }
        }
        $subOptions[] =
            [
                'label' => 'Store View',
                'value' => 'store_view'
            ];
        $subOptions[] =
            [
                'label' =>'Store Name',
                'value' => 'store_name'
            ];
        unset($attributeCollection);
        if (!$withoutGroup) {
            $options[] = [
                'label' => __('Category Attributes'),
                'optgroup-name' => 'product_attributes',
                'value' => $subOptions
            ];
        } else {
            $options += $subOptions;
        }

        return $options;
    }

    public function getAttributeCategoryCollection()
    {
        $this->attributeCategoryCollection = $this->attributeCategoryFactory
            ->create()
            ->setOrder('attribute_code', AbstractDb::SORT_ORDER_ASC);

        return $this->attributeCategoryCollection;
    }

    /**
     * @return array
     */
    protected function getAllFields($object)
    {
        $options = [];
        foreach ($object->getAllFields() as $field) {
            $options[] = is_array($field) ? $field : ['label' => $field, 'value' => $field];
        }

        return $options;
    }

    public function getOptions($entity)
    {
        if (in_array($entity, [
            self::CATALOG_PRODUCT,
            self::CATALOG_CATEGORY
        ])) {
            $options = $this->getAttributeCatalog();
            $newOptions[$entity] = $options;
        } else {
            $configes = $this->config->getEntities();
            if (isset($configes[$entity])) {
                try {
                    $object = $this->entityFactory->create($configes[$entity]['model']);
                    $newOptions[$entity] = $this->getAllFields($object);
                } catch (\Exception $e) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Please enter a correct entity model.')
                    );
                }
            }
        }

        return $newOptions;
    }
}
