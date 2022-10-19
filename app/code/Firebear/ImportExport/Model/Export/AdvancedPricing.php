<?php

/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export;

use Firebear\ImportExport\Traits\Export\Entity as ExportTrait;
use InvalidArgumentException;
use Magento\AdvancedPricingImportExport\Model\Export\AdvancedPricing as MagentoAdvancedPricing;
use Magento\AdvancedPricingImportExport\Model\Import\AdvancedPricing as ImportAdvancedPricing;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\Exception\LocalizedException;
use Magento\ImportExport\Model\Export as ModelExport;
use Magento\Store\Model\Store;
use Throwable;

/**
 * Class AdvancedPricing
 *
 * @package Firebear\ImportExport\Model\Export
 */
class AdvancedPricing extends MagentoAdvancedPricing implements EntityInterface
{
    use ExportTrait;

    /**
     * @return array
     */
    protected function loadCollection(): array
    {
        $data = [];

        /** @var ProductCollection $collection */
        $collection = $this->_getEntityCollection();
        foreach (array_keys($this->_storeIdToCode) as $storeId) {
            $collection->setStoreId($storeId);
            foreach ($collection as $itemId => $item) {
                $data[$itemId][$storeId] = $item;
            }
        }
        $collection->clear();

        return $data;
    }

    /**
     * @return array
     */
    protected function getExportData()
    {
        if ($this->_passTierPrice) {
            return [];
        }

        $exportData = [];
        try {
            $productsByStores = $this->loadCollection();

            if (!empty(count($productsByStores))) {
                $tierPricesData = $this->fetchTierPrices($this->getProductsIdByLinkField($productsByStores));
                $exportData = $this->prepareExportData($productsByStores, $tierPricesData);
                if (!empty($exportData)) {
                    asort($exportData);
                }
            }
        } catch (Throwable $e) {
            $this->_logger->critical($e);
        }
        $newData = $this->changeData($exportData);
        $this->_headerColumns = $this->changeHeaders($this->_headerColumns);

        return $newData;
    }

    /**
     * @param array $productsByStores
     * @return array
     */
    protected function getProductsIdByLinkField(array $productsByStores)
    {
        $productsId = [];
        $productEntityLinkField = $this->getProductEntityLinkField();
        foreach ($productsByStores as $productsByStore) {
            if (current($productsByStore)) {
                $product = current($productsByStore);
                $id = $product->getData($productEntityLinkField);
                if ($id !== null) {
                    $productsId[] = $id;
                }
            }
        }
        return $productsId;
    }

    /**
     * Prepare data for export.
     *
     * @param array $productsData Products to export.
     * @param array $tierPricesData Their tier prices.
     * @return array Export rows to display.
     * @throws LocalizedException
     */
    private function prepareExportData(
        array $productsData,
        array $tierPricesData
    ): array {
        $defaultStoreId = Store::DEFAULT_STORE_ID;
        $productEntityLinkField = $this->getProductEntityLinkField();
        //Assigning SKUs to tier prices data.
        $productLinkIdToSkuMap = [];
        foreach ($productsData as $productData) {
            $arrKey = $productData[$defaultStoreId][$productEntityLinkField];
            $productLinkIdToSkuMap[$arrKey] = $productData[$defaultStoreId]['sku'];
        }

        //Adding products' SKUs to tier price data.
        $linkedTierPricesData = [];
        foreach ($tierPricesData as $tierPriceData) {
            if (isset($productLinkIdToSkuMap[$tierPriceData['product_link_id']])) {
                $sku = $productLinkIdToSkuMap[$tierPriceData['product_link_id']];
                $linkedTierPricesData[] = array_merge(
                    $tierPriceData,
                    [ImportAdvancedPricing::COL_SKU => $sku]
                );
            }
        }

        //Formatting data for export.
        $customExportData = [];
        foreach ($linkedTierPricesData as $row) {
            $customExportData[] = $this->createExportRow($row);
        }

        return $customExportData;
    }

    /**
     * Creating export-formatted row from tier price.
     *
     * @param array $tierPriceData Tier price information.
     * @return array Formatted for export tier price information.
     * @throws LocalizedException
     */
    private function createExportRow(array $tierPriceData): array
    {
        //List of columns to display in export row.
        $exportRow = $this->templateExportData;

        foreach (array_keys($exportRow) as $keyTemplate) {
            if (array_key_exists($keyTemplate, $tierPriceData)) {
                if (in_array($keyTemplate, $this->_priceWebsite)) {
                    //If it's website column then getting website code.
                    $exportRow[$keyTemplate] = $this->_getWebsiteCode(
                        $tierPriceData[$keyTemplate]
                    );
                } elseif (in_array($keyTemplate, $this->_priceCustomerGroup)) {
                    //If it's customer group column then getting customer
                    //group name by ID.
                    $exportRow[$keyTemplate] = $this->_getCustomerGroupById(
                        $tierPriceData[$keyTemplate],
                        $tierPriceData[ImportAdvancedPricing::VALUE_ALL_GROUPS]
                    );
                    unset($exportRow[ImportAdvancedPricing::VALUE_ALL_GROUPS]);
                } elseif ($keyTemplate === ImportAdvancedPricing::COL_TIER_PRICE
                ) {
                    //If it's price column then getting value and type
                    //of tier price.
                    $exportRow[$keyTemplate]
                        = $tierPriceData[ImportAdvancedPricing::COL_TIER_PRICE_PERCENTAGE_VALUE]
                        ? $tierPriceData[ImportAdvancedPricing::COL_TIER_PRICE_PERCENTAGE_VALUE]
                        : $tierPriceData[ImportAdvancedPricing::COL_TIER_PRICE];
                    $exportRow[ImportAdvancedPricing::COL_TIER_PRICE_TYPE]
                        = $this->tierPriceTypeValue($tierPriceData);
                } else {
                    //Any other column just goes as is.
                    $exportRow[$keyTemplate] = $tierPriceData[$keyTemplate];
                }
            }
        }

        return $exportRow;
    }

    /**
     * Check type for tier price.
     *
     * @param array $tierPriceData
     * @return string
     */
    private function tierPriceTypeValue(array $tierPriceData): string
    {
        return $tierPriceData[ImportAdvancedPricing::COL_TIER_PRICE_PERCENTAGE_VALUE]
            ? ImportAdvancedPricing::TIER_PRICE_TYPE_PERCENT
            : ImportAdvancedPricing::TIER_PRICE_TYPE_FIXED;
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
        while (true) {
            ++$page;
            /** @var ProductCollection $entityCollection */
            $entityCollection = $this->_getEntityCollection(true);

            $entityCollection->setOrder('has_options', 'asc');
            $entityCollection->setStoreId(Store::DEFAULT_STORE_ID);
            $this->_prepareEntityCollection($entityCollection);

            $this->paginateCollection($page, $this->getItemsPerPage());
            if ($entityCollection->count() == 0) {
                break;
            }
            $exportData = $this->getExportData();
            if ($page == 1) {
                $writer->setHeaderCols($this->_getHeaderColumns());
            }
            foreach ($exportData as $dataRow) {
                $writer->writeRow($dataRow);
                $counts++;
            }
            if ($entityCollection->getCurPage() >= $entityCollection->getLastPageNumber()) {
                break;
            }
        }
        return [$writer->getContents(), $counts];
    }

    /**
     * Retrieve entity field for export
     *
     * @return array
     */
    public function getFieldsForExport()
    {
        return array_keys($this->templateExportData);
    }

    /**
     * Get header columns
     *
     * @return string[]
     */
    public function _getHeaderColumns()
    {
        $headers = $this->getFieldsForExport();

        return $this->changeHeaders($headers);
    }

    /**
     * Retrieve entity field for filter
     *
     * @return array
     */
    public function getFieldsForFilter()
    {
        $options = [];
        foreach ($this->getFieldsForExport() as $field) {
            $options[] = [
                'label' => $field,
                'value' => $field
            ];
        }
        return ['advanced_pricing' => $options];
    }

    /**
     * Retrieve entity field columns
     *
     * @return array
     */
    public function getFieldColumns()
    {
        $options = [];
        foreach ($this->getFieldsForExport() as $field) {
            $select = [];
            $type = 'text';
            $options['advanced_pricing'][] = ['field' => $field, 'type' => $type, 'select' => $select];
        }
        return $options;
    }

    /**
     * Load tier prices for given products.
     *
     * @param string[] $productIds Link IDs of products to find tier prices for.
     *
     * @return array Tier prices data.
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function fetchTierPrices(array $productIds): array
    {
        if (empty($productIds)) {
            throw new InvalidArgumentException('Can only load tier prices for specific products');
        }

        $pricesTable = ImportAdvancedPricing::TABLE_TIER_PRICE;
        $exportFilter = null;
        $priceFromFilter = null;
        $priceToFilter = null;
        $numeric = null;
        if (isset($this->_parameters[ModelExport::FILTER_ELEMENT_GROUP])) {
            $exportFilter = $this->_parameters[ModelExport::FILTER_ELEMENT_GROUP];
        }
        $productEntityLinkField = $this->getProductEntityLinkField();
        $selectFields = [
            ImportAdvancedPricing::COL_TIER_PRICE_WEBSITE => 'ap.website_id',
            ImportAdvancedPricing::VALUE_ALL_GROUPS => 'ap.all_groups',
            ImportAdvancedPricing::COL_TIER_PRICE_CUSTOMER_GROUP => 'ap.customer_group_id',
            ImportAdvancedPricing::COL_TIER_PRICE_QTY => 'ap.qty',
            ImportAdvancedPricing::COL_TIER_PRICE => 'ap.value',
            ImportAdvancedPricing::COL_TIER_PRICE_PERCENTAGE_VALUE => 'ap.percentage_value',
            'product_link_id' => 'ap.' . $productEntityLinkField,
        ];

        if (!empty($exportFilter) && array_key_exists('tier_price', $exportFilter)) {
            if (substr_count($exportFilter['tier_price'], "-")) {
                $rangeTierPrice = explode('-', $exportFilter['tier_price']);
                $priceFromFilter = $rangeTierPrice[0];
                $priceToFilter = $rangeTierPrice[1];
            }
            if (is_numeric($exportFilter['tier_price'])) {
                $numeric = $exportFilter['tier_price'];
            }
        }

        if (!empty($exportFilter) && !empty($exportFilter)) {
            if (isset($exportFilter['tier_price_website'])) {
                $website = $exportFilter['tier_price_website'];
            }
            if (isset($exportFilter['tier_price_qty'])) {
                $qty = $exportFilter['tier_price_qty'];
            }
            if (isset($exportFilter['tier_price_customer_group'])) {
                $customer_group = $exportFilter['tier_price_customer_group'];
            }
        }

        $inProductIds = implode("','", $productIds);

        $select = $this->_connection->select()
            ->from(['ap' => $this->_resource->getTableName($pricesTable)], $selectFields)
            ->where('ap.' . $productEntityLinkField . ' IN (\'' . $inProductIds . '\')');

        if (isset($qty) && !empty($qty)) {
            $select->where('ap.qty = ?', $qty);
        }
        if (isset($website) && !empty($website)) {
            $select->where('ap.website_id = ?', trim($website));
        }
        if (isset($customer_group) && !empty($customer_group)) {
            $select->where('ap.customer_group_id = ?', $customer_group);
        }

        if (($priceFromFilter !== null) && ($priceToFilter !== null)) {
            $select->where('ap.value >= ' . $priceFromFilter . ' AND ap.value <= ' . $priceToFilter);
            $select->orWhere(
                'ap.percentage_value >= ' . $priceFromFilter . ' AND ap.percentage_value <= ' . $priceToFilter
            );
        }
        if ($numeric !== null) {
            $select->where('ap.value = ?', $numeric);
            $select->orWhere('ap.percentage_value = ?', $numeric);
        }
        if (empty($priceFromFilter) && empty($priceToFilter) && empty($numeric)) {
            $select->where('ap.value  IS NOT NULL');
        }

        return $this->_connection->fetchAll($select);
    }
}
