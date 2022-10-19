<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Product\Type;

use Magento\CatalogImportExport\Model\Import\Product as ImportProduct;
use Magento\ImportExport\Model\Import;

/**
 * Class Configurable
 *
 * @package Firebear\ImportExport\Model\Import\Product\Type
 */
class Configurable extends \Magento\ConfigurableImportExport\Model\Import\Product\Type\Configurable
{
    use \Firebear\ImportExport\Traits\Import\Product\Type;

    const ERROR_INVALID_PRICE_CORRECTION = 'invalidPriceCorr';

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory
     */
    protected $attributeFactory;

    public static $specialAttributes = [
        '_super_products_sku',
        '_super_attribute_code',
        '_super_attribute_option',
        '_super_attribute_price_corr',
        '_super_attribute_price_website',
    ];

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    protected $defaults = [];

    /**
     * @var \Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory
     */
    protected $resourceFactory;

    protected $manager;

    /**
     * Configurable constructor.
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attrSetColFac
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $prodAttrColFac
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param array $params
     * @param \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypesConfig
     * @param \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $_productColFac
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attrSetColFac,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $prodAttrColFac,
        \Magento\Framework\App\ResourceConnection $resource,
        array $params,
        \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypesConfig,
        \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $_productColFac,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory $attributeFactory,
        \Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory $resourceModelFactory,
        \Magento\Framework\Module\Manager $moduleManager
    ) {
        parent::__construct(
            $attrSetColFac,
            $prodAttrColFac,
            $resource,
            $params,
            $productTypesConfig,
            $resourceHelper,
            $_productColFac
        );
        $this->registry = $registry;
        $this->_messageTemplates[self::ERROR_INVALID_PRICE_CORRECTION] =
            'Super attribute price correction value is invalid';
        $this->attributeFactory = $attributeFactory;
        $this->resourceFactory = $resourceModelFactory;
        $this->manager = $moduleManager;
    }

    /**
     * @param array $rowData
     * @param int $rowNum
     * @return bool
     */
    protected function _isParticularAttributesValid(array $rowData, $rowNum)
    {
        $options = $this->registry->registry('firebear_create_attr');
        if (!empty($rowData['_super_attribute_code'])) {
            $superAttrCode = $rowData['_super_attribute_code'];
            if (!$superAttrCode == 'default') {
                if (!$this->_isAttributeSuper($superAttrCode)) {
                    $this->_entityModel->addRowError(
                        __('Attribute with code "%1" is not super.', $superAttrCode),
                        $rowNum
                    );
                    return false;
                } elseif (isset($rowData['_super_attribute_option']) && $rowData['_super_attribute_option'] !== '') {
                    $optionKey = strtolower($rowData['_super_attribute_option']);
                    if (!empty($options) && isset($options[$superAttrCode])) {
                        $this->_superAttributes[$superAttrCode]['options'] = $options[$superAttrCode];
                    }

                    if (!isset($this->_superAttributes[$superAttrCode]['options'][$optionKey])) {
                        if (!$this->createAttributeValues($superAttrCode, $optionKey)) {
                            $this->_entityModel->addRowError(self::ERROR_INVALID_OPTION_VALUE, $rowNum);
                            return false;
                        }
                    }

                    if (!empty($rowData['super_attribute_price_corr'])
                        && !$this->_isPriceCorr($rowData['super_attribute_price_corr'])) {
                        $this->_entityModel->addRowError(self::ERROR_INVALID_PRICE_CORRECTION, $rowNum);
                        return false;
                    }
                }
            }
        }

        return true;
    }

    public function saveData()
    {
        $newSkus = $this->_entityModel->getNewSku();
        $oldSkus = $this->_entityModel->getOldSku();
        $this->_productSuperData = [];
        $this->_productData = null;
        while ($bunch = $this->_entityModel->getNextBunch()) {
            $bunch = $this->changeData($bunch);
            if (Import::BEHAVIOR_APPEND == $this->_entityModel->getBehavior()) {
                $this->_loadSkuSuperDataForBunch($bunch);
            }
            if (!$this->configurableInBunch($bunch)) {
                continue;
            }

            $this->_superAttributesData = [
                'attributes' => [],
                'labels' => [],
                'super_link' => [],
                'relation' => [],
            ];

            $this->_simpleIdsToDelete = [];

            $this->_loadSkuSuperAttributeValues($bunch, $newSkus, $oldSkus);

            foreach ($bunch as $rowNum => $rowData) {
                if (!$this->_entityModel->isRowAllowedToImport($rowData, $rowNum)) {
                    continue;
                }
                // remember SCOPE_DEFAULT row data
                $scope = $this->_entityModel->getRowScope($rowData);
                if ((ImportProduct::SCOPE_DEFAULT == $scope || ImportProduct::SCOPE_STORE == $scope)
                    && !empty($rowData[ImportProduct::COL_SKU])
                ) {
                    if (version_compare($this->_entityModel->getProductMetadata()->getVersion(), '2.2.0', '>=')) {
                        $sku = strtolower($rowData[ImportProduct::COL_SKU]);
                    } else {
                        $sku = $rowData[ImportProduct::COL_SKU];
                    }
                    $this->_productData = isset($newSkus[$sku]) ?
                        $newSkus[$sku] : $oldSkus[$sku];

                    if ($this->_productData['type_id'] != $this->_type) {
                        $this->_productData = null;
                        continue;
                    }

                    $this->_collectSuperData($rowData);
                }
            }
            $this->_processSuperData();
            $this->_deleteData();
            $this->_insertData();
        }

        return $this;
    }

    /**
     * @param $bunch
     * @return array
     */
    protected function changeData($bunch)
    {
        $newBunch = [];
        foreach ($bunch as $key => $data) {
            if (!in_array(strtolower($data['sku']), $this->_entityModel->getNotValidSkus())) {
                $newBunch[$key] = $data;
            }
        }

        return $newBunch;
    }

    public function createAttributeValues($attrCode, $attrValue)
    {
        $options = [];
        $attribute = $this->attributeFactory->create();
        $attribute->loadByCode(\Magento\Catalog\Model\Product::ENTITY, $attrCode);
        $optionsCount = count($attribute->getOptions());
        switch ($attribute->getFrontendInput()) {
            case 'select':
                $options[$attribute->getId()][] = [
                    'sort_order' => $optionsCount + 1,
                    'value' => $attrValue,
                    'code' => $attrCode
                ];
                break;
            case 'multiselect':
                foreach (explode($this->_entityModel->getMultipleValueSeparator(), $attrValue) as $value) {
                    $options[$attribute->getId()][] = [
                        'sort_order' => $optionsCount + 1,
                        'value' => $value,
                        'code' => $attrCode
                    ];
                }
                break;
            default:
                break;
        }

        if (!empty($options)) {
            foreach ($options as $attributeId => $optionsArray) {
                foreach ($optionsArray as $option) {
                    /**
                     * @see \Magento\Eav\Model\ResourceModel\Entity\Attribute::_updateAttributeOption()
                     */
                    $connection = $this->_connection;
                    $resource = $this->resourceFactory->create();
                    $table = $resource->getTable('eav_attribute_option');
                    $data = ['attribute_id' => $attributeId, 'sort_order' => $option['sort_order']];
                    $connection->insert($table, $data);
                    $intOptionId = $connection->lastInsertId($table);

                    $table = $resource->getTable('eav_attribute_option_value');
                    $data = ['option_id' => $intOptionId, 'store_id' => 0, 'value' => $option['value']];
                    $connection->insert($table, $data);
                    $this->addAttributeOption($option['code'], strtolower($option['value']), $intOptionId);
                }
            }

            return true;
        }

        return false;
    }

    protected function _deleteData()
    {
        parent::_deleteData();
        if ($this->manager->isEnabled('Firebear_ConfigurableProducts')) {
            $linkTable = $this->_resource->getTableName('icp_catalog_product_default_super_link');
            if (($this->_entityModel->getBehavior() == Import::BEHAVIOR_APPEND)
                && !empty($this->_productSuperData['product_id'])
            ) {
                error_log("da2222");
                $quoted = $this->connection->quoteInto('IN (?)', [$this->_productSuperData['product_id']]);
                $this->connection->delete($linkTable, "parent_id {$quoted}");
            }
        }
        return $this;
    }

    /**
     *  Collected link data insertion.
     *
     * @return $this
     * @throws \Zend_Db_Exception
     */
    protected function _insertData()
    {
        parent::_insertData();
        if ($this->manager->isEnabled('Firebear_ConfigurableProducts')) {
            if (!empty($this->defaults)) {
                $linkTable = $this->_resource->getTableName('icp_catalog_product_default_super_link');
                $list = $this->_superAttributesData['super_link'];
                foreach ($list as $key => $element) {
                    if (!in_array($element['product_id'], $this->defaults)) {
                        unset($list[$key]);
                    }
                }
                if ($list) {
                    $this->connection->insertOnDuplicate($linkTable, $list);
                }
            }
        }

        return $this;
    }

    protected function _parseVariations($rowData)
    {
        $additionalRows = [];
        if (!isset($rowData['configurable_variations'])) {
            return $additionalRows;
        }
        $variations = explode(ImportProduct::PSEUDO_MULTI_LINE_SEPARATOR, $rowData['configurable_variations']);
        foreach ($variations as $variation) {
            $fieldAndValuePairsText = explode($this->_entityModel->getMultipleValueSeparator(), $variation);
            $additionalRow = [];
            $fieldAndValuePairs = [];
            foreach ($fieldAndValuePairsText as $nameAndValue) {
                $nameAndValue = explode(ImportProduct::PAIR_NAME_VALUE_SEPARATOR, $nameAndValue);
                if (!empty($nameAndValue)) {
                    $value = isset($nameAndValue[1]) ? trim($nameAndValue[1]) : '';
                    $fieldName  = trim($nameAndValue[0]);
                    if ($fieldName) {
                        $fieldAndValuePairs[$fieldName] = $value;
                    }
                }
            }

            if (!empty($fieldAndValuePairs['sku'])) {
                $position = 0;

                if (version_compare($this->_entityModel->getProductMetadata()->getVersion(), '2.2.0', '>=')) {
                    $additionalRow['_super_products_sku'] = strtolower($fieldAndValuePairs['sku']);
                } else {
                    $additionalRow['_super_products_sku'] = $fieldAndValuePairs['sku'];
                }
                unset($fieldAndValuePairs['sku']);
                $additionalRow['display'] = isset($fieldAndValuePairs['display']) ? $fieldAndValuePairs['display'] : 1;
                unset($fieldAndValuePairs['display']);
                if (isset($fieldAndValuePairs['default'])) {
                    $additionalRow['default'] = $fieldAndValuePairs['default'];
                }
                foreach ($fieldAndValuePairs as $attrCode => $attrValue) {
                        $additionalRow['_super_attribute_code'] = $attrCode;
                        $additionalRow['_super_attribute_option'] = $attrValue;
                        $additionalRow['_super_attribute_position'] = $position;
                        $additionalRows[] = $additionalRow;
                        $additionalRow = [];
                        $position++;
                }
            }
        }

        return $additionalRows;
    }

    protected function _collectSuperData($rowData)
    {
        $this->_productData['attr_set_code'] = $this->_productData['attr_set_code'] ?? $rowData['attribute_set_code'];
        return parent::_collectSuperData($rowData);
    }

    protected function _loadSkuSuperAttributeValues($bunch, $newSku, $oldSku)
    {
        if ($this->_superAttributes) {
            $attrSetIdToName = $this->_entityModel->getAttrSetIdToName();

            $ids = [];
            foreach ($bunch as $rowData) {
                $dataWithExtraVirtualRows = $this->_parseVariations($rowData);
                if (!empty($dataWithExtraVirtualRows)) {
                    array_unshift($dataWithExtraVirtualRows, $rowData);
                } else {
                    $dataWithExtraVirtualRows[] = $rowData;
                }
                foreach ($dataWithExtraVirtualRows as $data) {
                    if (!empty($data['_super_products_sku'])) {
                        if (isset($newSku[$data['_super_products_sku']])) {
                            if (isset($data['default']) && $data['default']) {
                                $this->defaults[] =
                                    $newSku[$data['_super_products_sku']][$this->getProductEntityLinkField()];
                            }
                            $ids[] = $newSku[$data['_super_products_sku']][$this->getProductEntityLinkField()];
                        } elseif (isset($oldSku[$data['_super_products_sku']])) {
                            if (isset($data['default']) && $data['default']) {
                                $this->defaults[] =
                                    $oldSku[$data['_super_products_sku']][$this->getProductEntityLinkField()];
                            }
                            $ids[] = $oldSku[$data['_super_products_sku']][$this->getProductEntityLinkField()];
                        }
                    }
                }
            }

            foreach ($this->_productColFac
                         ->create()
                         ->addFieldToFilter(
                             'type_id',
                             $this->_productTypesConfig
                             ->getComposableTypes()
                         )->addFieldToFilter(
                             $this->getProductEntityLinkField(),
                             ['in' => $ids]
                         )->addAttributeToSelect(
                             array_keys($this->_superAttributes)
                         ) as $product) {
                $attributeSetName = $attrSetIdToName[$product->getAttributeSetId()];

                $data = array_intersect_key(
                    $product->getData(),
                    $this->_superAttributes
                );
                foreach ($data as $attrCode => $value) {
                    $attrId = $this->_superAttributes[$attrCode]['id'];
                    $productId = $product->getData(
                        $this->getProductEntityLinkField()
                    );
                    $this->_skuSuperAttributeValues[$attributeSetName][$productId][$attrId] = $value;
                }
            }
        }

        return $this;
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
