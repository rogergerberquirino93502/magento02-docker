<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Entity\Import\Attributes;

use Firebear\ImportExport\Helper\Data;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Firebear\ImportExport\Model\Import\Product;
use Firebear\ImportExport\Model\Import\Customer;
use Firebear\ImportExport\Model\Import\Address;
use Firebear\ImportExport\Model\Import\CustomerComposite;
use Firebear\ImportExport\Model\Source\Import\Config;
use Magento\ImportExport\Model\Import\Entity\Factory;

/**
 * Class Options
 */
class SystemOptions implements OptionSourceInterface
{

    const CATALOG_PRODUCT = 'catalog_product';

    const CATALOG_CATEGORY = 'catalog_category';

    const ADVANCED_PRICING = 'advanced_pricing';

    const RELATED_PRODUCT_ATTRIBUTE = 'related_skus';
    const CROSS_SELLS_PRODUCT_ATTRIBUTE = 'crosssell_skus';
    const UP_SELLS_PRODUCT_ATTRIBUTE = 'upsell_skus';

    const ADDITIONAL_IMAGES_ATTRIBUTE = 'additional_images';
    const ADDITIONAL_IMAGE_LABELS_ATTRIBUTE = 'additional_image_labels';

    /**
     * @var array
     */
    protected $options;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory
     */
    protected $attributeFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    protected $attributeCollection;

    /**
     * @var Product
     */
    protected $productImportModel;

    /**
     * @var Customer
     */
    protected $customer;

    /**
     * @var Address
     */
    protected $address;

    /**
     * @var CustomerComposite
     */
    protected $composite;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Factory
     */
    protected $entityFactory;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * Options constructor.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeFactory
     * @param Product $productImportModel
     * @param Customer $customer
     * @param Address $address
     * @param CustomerComposite $composite
     * @param Config $config
     * @param Factory $entityFactory
     * @param Data $helper
     */
    public function __construct(
        CollectionFactory $attributeFactory,
        Config $config,
        Factory $entityFactory,
        Data $helper
    ) {
        $this->attributeFactory = $attributeFactory;
        $this->config = $config;
        $this->entityFactory = $entityFactory;
        $this->helper = $helper;
    }

    /**
     * @param int $withoutGroup
     *
     * @return array
     */
    public function toOptionArray($withoutGroup = 0)
    {

        $options = $this->getAttributeCatalog($withoutGroup);

        $this->options = $options;

        return $this->options;
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

    /**
     * @param int $withoutGroup
     *
     * @return array
     */
    protected function getAttributeCatalog($withoutGroup = 0)
    {
        $attributeCollection = $this->getAttributeCollection()
            ->addFieldToFilter('attribute_code', ['nin' => ['sku', 'url_key']]);
        $subOptions = [];
        foreach ($attributeCollection as $attribute) {
            if ($attribute->getAttributeCode() == 'media_gallery') {
                $subOptions[] =
                    [
                        'label' => self::ADDITIONAL_IMAGES_ATTRIBUTE . ' (' .  __('Additional images') . ')',
                        'value' => self::ADDITIONAL_IMAGES_ATTRIBUTE
                    ];
                $subOptions[] =
                    [
                        'label' => self::ADDITIONAL_IMAGE_LABELS_ATTRIBUTE .' (' .  __('Additional image labels') . ')',
                        'value' => self::ADDITIONAL_IMAGE_LABELS_ATTRIBUTE
                    ];
                continue;
            }
            $label = (!$withoutGroup) ?
                $attribute->getAttributeCode() . ' (' . $attribute->getFrontendLabel() . ')' :
                $attribute->getAttributeCode();
            $subOptions[] =
                [
                    'label' => $label,
                    'value' => $attribute->getAttributeCode()
                ];
        }
        $subOptions[] = [
            'label' => self::RELATED_PRODUCT_ATTRIBUTE . ' (' .  __('Related products') . ')',
            'value' => self::RELATED_PRODUCT_ATTRIBUTE
        ];
        $subOptions[] = [
            'label' => self::CROSS_SELLS_PRODUCT_ATTRIBUTE . ' (' .  __('Cross-Sells products') . ')',
            'value' => self::CROSS_SELLS_PRODUCT_ATTRIBUTE
        ];
        $subOptions[] = [
            'label' => self::UP_SELLS_PRODUCT_ATTRIBUTE . ' (' .  __('Up-Sells products') . ')',
            'value' => self::UP_SELLS_PRODUCT_ATTRIBUTE
        ];
        return $subOptions;
    }
}
