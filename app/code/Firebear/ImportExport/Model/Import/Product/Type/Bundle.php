<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Product\Type;

use Magento\CatalogImportExport\Model\Import\Product as ImportProduct;
use Magento\Framework\App\ObjectManager;
use Magento\BundleImportExport\Model\Import\Product\Type\Bundle\RelationsDataSaver;

/**
 * Class Bundle
 *
 * @package Firebear\ImportExport\Model\Import\Product\Type
 */
class Bundle extends \Magento\BundleImportExport\Model\Import\Product\Type\Bundle
{
    use \Firebear\ImportExport\Traits\Import\Product\Type;

    private $relationsDataSaver;
    protected $resource;

    public static $specialAttributes = [
        'price_type',
        'weight_type',
        'sku_type',
    ];

    /**
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attrSetColFac
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $prodAttrColFac
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param array $params
     * @param \Magento\Framework\EntityManager\MetadataPool|null $metadataPool
     * @param \Magento\BundleImportExport\Model\Import\Product\Type\Bundle\RelationsDataSaver $relationsDataSaver
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attrSetColFac,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $prodAttrColFac,
        \Magento\Framework\App\ResourceConnection $resource,
        array $params,
        \Magento\Framework\EntityManager\MetadataPool $metadataPool = null,
        RelationsDataSaver $relationsDataSaver = null
    ) {
        parent::__construct($attrSetColFac, $prodAttrColFac, $resource, $params, $metadataPool);

        $this->relationsDataSaver = $relationsDataSaver
            ?: ObjectManager::getInstance()->get(RelationsDataSaver::class);
        $this->resource = $resource;
    }

    /**
     * Insert selections.
     *
     * @return \Magento\CatalogImportExport\Model\Import\Product\Type\AbstractType
     */
    protected function insertSelections()
    {
        $selections = [];

        foreach ($this->_cachedOptions as $productId => $options) {
            foreach ($options as $option) {
                $index = 0;
                foreach ($option['selections'] as $selection) {
                    if (isset($selection['position'])) {
                        $index = $selection['position'];
                    }
                    if ($tmpArray = $this->populateSelectionTemplate(
                        $selection,
                        $option['option_id'],
                        $productId,
                        $index
                    )) {
                        if (isset($tmpArray['selection_can_change_qty'], $selection['selection_can_change_qty'])
                        && $tmpArray['selection_can_change_qty'] != $selection['selection_can_change_qty']
                        ) {
                            $tmpArray['selection_can_change_qty'] = $selection['selection_can_change_qty'] ?? 1;
                        }
                        $selections[] = $tmpArray;
                        $index++;
                    }
                }
            }
        }

        $selectedSelections = [];

        foreach ($selections as $key => $selection) {
            $productId = $selection['product_id'];
            if (isset($selectedSelections[$selection['option_id']])) {
                if (isset($selectedSelections[$selection['option_id']][$productId]) &&
                    $selectedSelections[$selection['option_id']][$productId] == $productId) {
                    unset($selections[$key]);
                } else {
                    $selectedSelections[$selection['option_id']][$productId] = $productId;
                }
            } else {
                $selectedSelections[$selection['option_id']][$productId] = $productId;
            }
        }

        if (version_compare($this->_entityModel->getProductMetadata()->getVersion(), '2.2.0', '>=')) {
            $this->relationsDataSaver->saveSelections($selections);
        } else {
            $selectionTable = $this->_resource->getTableName('catalog_product_bundle_selection');
            if (!empty($selections)) {
                $this->connection->insertOnDuplicate(
                    $selectionTable,
                    $selections,
                    [
                        'selection_id',
                        'product_id',
                        'position',
                        'is_default',
                        'selection_price_type',
                        'selection_price_value',
                        'selection_qty',
                        'selection_can_change_qty'
                    ]
                );
            }
        }
        $this->saveCatalogProductRelation($selections);

        return $this;
    }

    /**
     * Insert data to catalog_product_relation table
     * Solve problem: bundle products always show out of stock in front-end
     */
    protected function saveCatalogProductRelation($selections)
    {
        if (!empty($selections)) {
            $catalogProductRelations = [];
            foreach ($selections as $selection) {
                $catalogProductRelations[] = [
                    'parent_id' => $selection['parent_product_id'],
                    'child_id' => $selection['product_id']
                ];
            }
            $this->resource->getConnection()->insertOnDuplicate(
                $this->resource->getTableName('catalog_product_relation'),
                $catalogProductRelations,
                [
                    'parent_id',
                    'child_id',
                ]
            );
        }
    }

    /**
     * @return $this|ImportProduct\Type\AbstractType
     */
    public function saveData()
    {
        if ($this->_entityModel->getBehavior() == \Magento\ImportExport\Model\Import::BEHAVIOR_DELETE) {
            $productIds = [];
            $newProducts = $this->_entityModel->getNewSku();
            while ($bunch = $this->_entityModel->getNextBunch()) {
                foreach ($bunch as $rowNum => $rowData) {
                    $productData = $newProducts[strtolower($rowData[ImportProduct::COL_SKU])];
                    $productIds[] = $productData[$this->getProductEntityLinkField()];
                }
                $this->deleteOptionsAndSelections($productIds);
            }
        } else {
            $newProducts = $this->_entityModel->getNewSku();
            while ($bunch = $this->_entityModel->getNextBunch()) {
                foreach ($bunch as $rowNum => $rowData) {
                    if (!$this->_entityModel->isRowAllowedToImport($rowData, $rowNum)) {
                        continue;
                    }
                    $productData = $newProducts[strtolower($rowData[ImportProduct::COL_SKU])];
                    if ($this->_type != $productData['type_id']) {
                        continue;
                    }
                    $this->parseSelections($rowData, $productData[$this->getProductEntityLinkField()]);
                }
                if (!empty($this->_cachedOptions)) {
                    $this->retrieveProductsByCachedSkus();
                    $this->populateExistingOptions();
                    $this->insertOptions();
                    $this->insertSelections();
                    $this->insertParentChildRelations();
                    $this->clear();
                }
            }
        }
        return $this;
    }

    /**
     * @return $this|ImportProduct\Type\AbstractType
     */
    private function insertParentChildRelations()
    {
        foreach ($this->_cachedOptions as $productId => $options) {
            $childIds = [];
            foreach ($options as $option) {
                foreach ($option['selections'] as $selection) {
                    if (!isset($this->_cachedSkuToProducts[$selection['sku']])) {
                        continue;
                    }
                    $childIds[] = $this->_cachedSkuToProducts[$selection['sku']];
                }
                $this->relationsDataSaver->saveProductRelations($productId, $childIds);
            }
        }

        return $this;
    }

    /**
     * Parse the option.
     *
     * @param array $values
     *
     * @return array
     */
    protected function parseOption($values)
    {
        $option = parent::parseOption($values);

        $select = $this->connection->select()->from(
            $this->_resource->getTableName('catalog_product_entity'),
            ['sku', 'entity_id']
        )->where(
            'sku = (?)',
            $option['sku']
        );

        $isSkuExists = $this->connection->fetchOne($select);
        if (!$isSkuExists) {
            unset($option['sku'], $option['name']);
        }

        return $option;
    }

    /**
     * Parse selections.
     *
     * @param array $rowData
     * @param int $entityId
     *
     * @return array
     */
    protected function parseSelections($rowData, $entityId)
    {
        $selections = parent::parseSelections($rowData, $entityId);
        foreach ($this->_cachedOptions as $productId => $options) {
            $childSkus = [];
            foreach ($options as $key => $option) {
                foreach ($option['selections'] as $selectionKey => $selection) {
                    if (in_array($selection['sku'], $childSkus)) {
                        unset($this->_cachedOptions[$productId][$key]['selections'][$selectionKey]);
                    } else {
                        $childSkus[] = $selection['sku'];
                    }
                }
            }
        }
        return $selections;
    }

    /**
     * @param array $rowData
     * @return array
     */
    protected function addAdditionalAttributes(array $rowData)
    {
        return [];
    }
}
