<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export\RowCustomizer;

use Firebear\ImportExport\Model\Import;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\CatalogImportExport\Model\Export\RowCustomizerInterface;
use Magento\CatalogImportExport\Model\Import\Product as ImportProduct;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection as AttrCollection;

/**
 * Class Tax
 *
 * @package Firebear\ImportExport\Model\Export\RowCustomizer
 */
class Tax implements RowCustomizerInterface
{
    const WEE_TAX_VARIATIONS_COLUMN = 'wee_tax_variations';

    /**
     * @var array
     */
    protected $weeTaxData = [];

    /**
     * @var string[]
     */
    private $weeTaxColumns = [
        self::WEE_TAX_VARIATIONS_COLUMN
    ];

    /**
     * @var \Magento\Weee\Model\ResourceModel\Attribute\Backend\Weee\Tax
     */
    protected $tax;

    /**
     * @var AttributeCollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var AttrCollection
     */
    protected $collectionAttr;

    /**
     * Tax constructor.
     *
     * @param \Magento\Weee\Model\ResourceModel\Attribute\Backend\Weee\Tax $tax
     * @param AttributeCollectionFactory $collectionFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Weee\Model\ResourceModel\Attribute\Backend\Weee\Tax $tax,
        AttributeCollectionFactory $collectionFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->tax = $tax;
        $this->collectionFactory = $collectionFactory;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
    }

    /**
     * Prepare configurable data for export
     *
     * @param ProductCollection $collection
     * @param int[] $productIds
     * @return void
     */
    public function prepareData($collection, $productIds)
    {
        $productCollection = clone $collection;
        $productCollection->addAttributeToFilter('entity_id', ['in' => $productIds]);
        while ($product = $productCollection->fetchItem()) {
            $variations = [];
            $collectionAttr = $this->getWeeAttributeCollection();
            if ($collectionAttr->getSize() > 0) {
                foreach ($collectionAttr as $item) {
                    $item->setScopeGlobal(1);
                    $tax = $this->getDataTax($product, $item);
                    if (!empty($tax)) {
                        foreach ($tax as $element) {
                            $str = 'name=' . $item->getAttributeCode()
                                . Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR .
                                'country=' . $element['country']
                                . Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR .
                                'state=' . $element['state']
                                . Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR .
                                'value=' . $element['value'];
                            if (isset($element['website_id'])) {
                                $str .= Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR .
                                    'website_id=' . $element['website_id'];
                            }
                            $variations[] = $str;
                        }
                    }
                }
            }
            $result = '';
            if (!empty($variations)) {
                $result = [
                    self::WEE_TAX_VARIATIONS_COLUMN => implode(
                        ImportProduct::PSEUDO_MULTI_LINE_SEPARATOR,
                        $variations
                    )
                ];
            }
            $this->weeTaxData[$product->getId()] = $result;
        }
    }

    /**
     * Get wee attribute collection. Should be cached into var to avoid useless 1 request per export item
     *
     * @return AttrCollection
     */
    protected function getWeeAttributeCollection()
    {
        if ($this->collectionAttr === null) {
            $this->collectionAttr = $this->collectionFactory->create();
            $this->collectionAttr->addFieldToFilter('frontend_input', 'weee');
        }

        return $this->collectionAttr;
    }

    /**
     * Set headers columns
     *
     * @param array $columns
     * @return array
     */
    public function addHeaderColumns($columns)
    {
        return array_merge($columns, $this->weeTaxColumns);
    }

    /**
     * Add configurable data for export
     *
     * @param array $dataRow
     * @param int $productId
     * @return array
     */
    public function addData($dataRow, $productId)
    {
        if (!empty($this->weeTaxData[$productId])) {
            $dataRow = array_merge($dataRow, $this->weeTaxData[$productId]);
        }
        return $dataRow;
    }

    /**
     * Calculate the largest links block
     *
     * @param array $additionalRowsCount
     * @param int $productId
     * @return array
     */
    public function getAdditionalRowsCount($additionalRowsCount, $productId)
    {
        if (!empty($this->weeTaxData[$productId])) {
            $additionalRowsCount = max($additionalRowsCount, count($this->weeTaxData[$productId]));
        }
        return $additionalRowsCount;
    }

    protected function getDataTax($product, $attribute)
    {
        $select = $this->tax->getConnection()->select()->from(
            $this->tax->getMainTable(),
            ['website_id', 'country', 'state', 'value']
        )->where(
            'entity_id = ?',
            (int)$product->getId()
        )->where(
            'attribute_id = ?',
            (int)$attribute->getId()
        );

        $storeId = $product->getStoreId();
        if ($storeId) {
            $select->where(
                'website_id IN (?)',
                [0, $this->storeManager->getStore($storeId)->getWebsiteId()]
            );
        }

        return $this->tax->getConnection()->fetchAll($select);
    }
}
