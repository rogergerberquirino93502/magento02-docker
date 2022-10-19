<?php

/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Product\Type;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogImportExport\Model\Import\Product as ImportProduct;
use Magento\Framework\EntityManager\MetadataPool;
use Firebear\ImportExport\Model\Import\Product;

/**
 * Class Tax
 *
 * @package Firebear\ImportExport\Model\Import\Product\Type
 */
class Tax extends \Magento\CatalogImportExport\Model\Import\Product\Type\AbstractType
{
    /**
     * Delimiter before product option value.
     */
    const BEFORE_OPTION_VALUE_DELIMITER = ';';

    /**
     * Pair value separator.
     */
    const PAIR_VALUE_SEPARATOR = '=';

    const WEE_TAX_VARIATIONS_COLUMN = 'wee_tax_variations';

    /**
     * Error codes
     */
    const ERROR_ATTRIBUTE_CODE_IS_NOT_SUPER = 'attrCodeIsNotSuper';

    const ERROR_INVALID_OPTION_VALUE = 'invalidOptionValue';

    /**
     * Validation failure message template definitions
     *
     * @var array
     *
     * Note: Some of these messages exceed maximum limit of 120 characters per line. Split up accordingly.
     */
    protected $_messageTemplates = [
        self::ERROR_ATTRIBUTE_CODE_IS_NOT_SUPER => 'Column configurable_variations: Attribute with code ' .
            '"%s" is not super',
        self::ERROR_INVALID_OPTION_VALUE => 'Column configurable_variations: Invalid option value for attribute "%s"',
    ];

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;

    /**
     * Instance of product collection factory.
     *
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productColFac;

    /**
     * Product data.
     *
     * @var array
     */
    protected $productData;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;
    /** @var \Magento\Catalog\Model\ProductTypes\ConfigInterface */
    protected $productTypesConfig;
    /** @var array */
    protected $cachedOptions = [];

    /**
     * Super attributes codes in a form of code => TRUE array pairs
     *
     * @var array
     */
    protected $_superAttributes = [];

    /**
     * Tax constructor.
     *
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attrSetColFac
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $prodAttrColFac
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param array $params
     * @param \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypesConfig
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $_productColFac
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attrSetColFac,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $prodAttrColFac,
        \Magento\Framework\App\ResourceConnection $resource,
        array $params,
        \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypesConfig,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $_productColFac,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        parent::__construct($attrSetColFac, $prodAttrColFac, $resource, $params);
        $this->productTypesConfig = $productTypesConfig;
        $this->productColFac = $_productColFac;
        $this->productRepository = $productRepository;
        $this->connection = $this->_entityModel->getConnection();
    }

    /**
     * Add attribute parameters to appropriate attribute set.
     *
     * @param string $attrSetName Name of attribute set.
     * @param array $attrParams Refined attribute parameters.
     * @param mixed $attribute
     * @return \Magento\CatalogImportExport\Model\Import\Product\Type\AbstractType
     */
    protected function _addAttributeParams($attrSetName, array $attrParams, $attribute)
    {
        // save super attributes for simpler and quicker search in future
        if ('select' == $attrParams['type'] && 1 == $attrParams['is_global']) {
            $this->_superAttributes[$attrParams['code']] = $attrParams;
        }
        return parent::_addAttributeParams($attrSetName, $attrParams, $attribute);
    }

    /**
     * Is attribute is super-attribute?
     *
     * @param string $attrCode
     * @return bool
     */
    protected function _isAttributeSuper($attrCode)
    {
        return isset($this->_superAttributes[$attrCode]);
    }

    /**
     * Validate particular attributes columns.
     *
     * @param array $rowData
     * @param int $rowNum
     *
     * @return bool
     */
    protected function _isParticularAttributesValid(array $rowData, $rowNum)
    {
        if (!empty($rowData['_super_attribute_code'])) {
            $superAttrCode = $rowData['_super_attribute_code'];

            if (!$this->_isAttributeSuper($superAttrCode)) {
                // check attribute superity
                $this->_entityModel->addRowError(self::ERROR_ATTRIBUTE_CODE_IS_NOT_SUPER, $rowNum, $superAttrCode);

                return false;
            } elseif (isset($rowData['_super_attribute_option']) && strlen($rowData['_super_attribute_option'])) {
                $optionKey = strtolower($rowData['_super_attribute_option']);
                if (!isset($this->_superAttributes[$superAttrCode]['options'][$optionKey])) {
                    $this->_entityModel->addRowError(self::ERROR_INVALID_OPTION_VALUE, $rowNum, $superAttrCode);
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Delete options and selections.
     *
     * @param array $productIds
     *
     * @return \Magento\CatalogImportExport\Model\Import\Product\Type\AbstractType
     */
    protected function deleteOptionsAndSelections($productIds)
    {
        $optionTable = $this->_resource->getTableName('weee_tax');
        $valuesIds = $this->connection->fetchAssoc($this->connection->select()->from(
            ['bov' => $optionTable],
            ['value_id']
        )->where(
            'entity_id IN (?)',
            $productIds
        ));
        $this->connection->delete(
            $optionTable,
            $this->connection->quoteInto('value_id IN (?)', array_keys($valuesIds))
        );
        $productIdsInWhere = $this->connection->quoteInto('parent_id IN (?)', $productIds);
        $this->connection->delete(
            $optionTable,
            $this->connection->quoteInto('parent_id IN (?)', $productIdsInWhere)
        );
        $this->connection->delete(
            $optionTable,
            $this->connection->quoteInto('parent_product_id IN (?)', $productIdsInWhere)
        );

        return $this;
    }

    protected function parseOption($values)
    {
        $option = [];
        foreach ($values as $keyValue) {
            $keyValue = trim($keyValue);
            $pos = strpos($keyValue, self::PAIR_VALUE_SEPARATOR);
            if ($pos !== false) {
                $key = substr($keyValue, 0, $pos);
                $value = substr($keyValue, $pos + 1);
                $option[$key] = $value;
            }
        }
        return $option;
    }

    protected function parseSelections($rowData, $entityId)
    {
        if (empty($rowData[self::WEE_TAX_VARIATIONS_COLUMN])) {
            return [];
        }

        $rowData[self::WEE_TAX_VARIATIONS_COLUMN] = str_replace(
            self::BEFORE_OPTION_VALUE_DELIMITER,
            $this->_entityModel->getMultipleValueSeparator(),
            $rowData[self::WEE_TAX_VARIATIONS_COLUMN]
        );
        $selections = explode(
            Product::PSEUDO_MULTI_LINE_SEPARATOR,
            $rowData[self::WEE_TAX_VARIATIONS_COLUMN]
        );

        foreach ($selections as $selection) {
            $values = explode($this->_entityModel->getMultipleValueSeparator(), $selection);
            $option = $this->parseOption($values);
            if (!empty($option)) {
                if (isset($option['name'])) {
                    $collect = $this->_prodAttrColFac->create()->addFieldToFilter('attribute_code', $option['name']);
                    if ($collect->getSize()) {
                        $option['attribute_id'] = $collect->getFirstItem()->getId();
                        unset($option['name']);
                        $option['entity_id'] = $entityId;
                        $this->cachedOptions[$entityId][] = $option;
                    }
                }
            }
        }
        return $selections;
    }

    /**
     * @return $this
     */
    protected function insertOptions()
    {
        foreach ($this->cachedOptions as $options) {
            foreach ($options as $option) {
                $select = $this->connection->select()->from(
                    $this->_resource->getTableName('weee_tax'),
                    ['value_id']
                )->where('entity_id=?', $option['entity_id'])
                    ->where('country=?', $option['country'])
                    ->where('state=?', $option['state'])
                    ->where('attribute_id=?', $option['attribute_id']);
                $optionIds = $this->connection->fetchAssoc(
                    $select
                );
                if (!empty($optionIds)) {
                    $optionIds = \array_shift($optionIds);
                    $option = \array_merge($optionIds, $option);
                }
                if (isset($option['website_id'])) {
                    $this->connection->insertOnDuplicate(
                        $this->_resource->getTableName('weee_tax'),
                        $option,
                        ['entity_id', 'country', 'value', 'state', 'attribute_id', 'website_id']
                    );
                } else {
                    $this->connection->insertOnDuplicate(
                        $this->_resource->getTableName('weee_tax'),
                        $option,
                        ['entity_id', 'country', 'value', 'state', 'attribute_id']
                    );
                }
            }
        }
        return $this;
    }

    /**
     * @return $this
     */
    protected function clear()
    {
        $this->cachedOptions = [];
        return $this;
    }

    /**
     * Save product type specific data.
     *
     * @throws \Exception
     * @return \Magento\CatalogImportExport\Model\Import\Product\Type\AbstractType
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function saveData()
    {
        if ($this->_entityModel->getBehavior() == \Magento\ImportExport\Model\Import::BEHAVIOR_DELETE) {
            $productIds = [];
            while ($bunch = $this->_entityModel->getNextBunch()) {
                foreach ($bunch as $rowNum => $rowData) {
                    $productIds[] = $this->productRepository->get($rowData['sku'])->getId();
                }
                $this->deleteOptionsAndSelections($productIds);
            }
        }
        if ($this->_entityModel->getBehavior() == \Magento\ImportExport\Model\Import::BEHAVIOR_APPEND) {
            while ($bunch = $this->_entityModel->getNextBunch()) {
                foreach ($bunch as $rowNum => $rowData) {
                    if (!$this->_entityModel->isRowAllowedToImport($rowData, $rowNum)) {
                        continue;
                    }
                    if (!empty($rowData[self::WEE_TAX_VARIATIONS_COLUMN])) {
                        $newSku = $this->_entityModel->getNewSku();
                        $collection = $this->productColFac->create()->addFieldToFilter('sku', $rowData['sku']);
                        if (!$collection->getSize()) {
                            $productData = $newSku[strtolower($rowData[Product::COL_SKU])];
                            $id = $productData[$this->getProductEntityLinkField()];
                        } else {
                            $id = $collection->getFirstItem()->getEntityId();
                        }
                        $this->parseSelections($rowData, $id);
                    }
                }

                if (!empty($this->cachedOptions)) {
                    $this->insertOptions();
                    $this->clear();
                }
            }
        }
        if ($this->_entityModel->getBehavior() == \Magento\ImportExport\Model\Import::BEHAVIOR_REPLACE) {
            while ($bunch = $this->_entityModel->getNextBunch()) {
                foreach ($bunch as $rowNum => $rowData) {
                    if (!$this->_entityModel->isRowAllowedToImport($rowData, $rowNum)) {
                        continue;
                    }
                    if (!empty($rowData[self::WEE_TAX_VARIATIONS_COLUMN])) {
                        $newSku = $this->_entityModel->getNewSku();
                        $collection = $this->productColFac->create()->addFieldToFilter('sku', $rowData['sku']);
                        if (!$collection->getSize()) {
                            $productData = $newSku[strtolower($rowData[Product::COL_SKU])];
                            $id = $productData[$this->getProductEntityLinkField()];
                        } else {
                            $id = $collection->getFirstItem()->getEntityId();
                        }
                        $productIds[] = $id;

                        $this->parseSelections($rowData, $id);
                    }
                }

                if (!empty($this->cachedOptions)) {
                    $this->deleteOptionsAndSelections($productIds);
                    $this->insertOptions();
                    $this->clear();
                }
            }
        }

        return $this;
    }
}
