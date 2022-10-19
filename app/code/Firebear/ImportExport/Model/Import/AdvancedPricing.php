<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import;

use Exception;
use Firebear\ImportExport\Helper\Data as ImportExportHelper;
use Firebear\ImportExport\Traits\Import\Entity as ImportTrait;
use InvalidArgumentException;
use Magento\AdvancedPricingImportExport\Model\Import\AdvancedPricing as MagentoAdvancedPricing;
use Magento\AdvancedPricingImportExport\Model\Import\AdvancedPricing\Validator;
use Magento\AdvancedPricingImportExport\Model\Import\AdvancedPricing\Validator\Website as WebsiteValidator;
use Magento\AdvancedPricingImportExport\Model\Import\AdvancedPricing\Validator\TierPrice as TierPriceValidator;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\CatalogImportExport\Model\Import\Product\RowValidatorInterface;
use Magento\CatalogImportExport\Model\Import\Product\RowValidatorInterface as ValidatorInterface;
use Magento\Catalog\Model\Product\Price\TierPrice;
use Magento\CatalogImportExport\Model\Import\Product\StoreResolver;
use Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\ImportExport\Model\Import;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class AdvancedPricing
 *
 * @package Firebear\ImportExport\Model\Import
 */
class AdvancedPricing extends MagentoAdvancedPricing
{
    use ImportTrait;

    const CLEARED_PRODUCTS_CACHE_ID = 'firebear_import_advanced_pricing_cleared_products_';

    /**
     * @var array
     */
    protected $checkDuplicates = [];

    /**
     * @var array
     */
    protected $messageTemplates = [
        RowValidatorInterface::ERROR_DUPLICATE_UNIQUE_ATTRIBUTE => 'Duplicate unique attribute'
    ];

    /**
     * @var bool
     */
    protected $_debugMode;

    /**
     * @var string
     */
    private $productEntityLinkField;

    /**
     * @var array
     */
    protected $entityProducts;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * Json Serializer
     *
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @param Context $context
     * @param DateTime $dateTime
     * @param ResourceModelFactory $resourceFactory
     * @param ProductModel $productModel
     * @param CatalogHelper $catalogHelper
     * @param StoreResolver $storeResolver
     * @param Product $importProduct
     * @param Validator $validator
     * @param WebsiteValidator $websiteValidator
     * @param TierPriceValidator $tierPriceValidator
     * @param ConsoleOutput $output
     * @param ImportExportHelper $helper
     * @param ProductMetadataInterface $productMetadata
     * @param CacheInterface $cache
     */
    public function __construct(
        Context $context,
        DateTime $dateTime,
        ResourceModelFactory $resourceFactory,
        ProductModel $productModel,
        CatalogHelper $catalogHelper,
        StoreResolver $storeResolver,
        Product $importProduct,
        Validator $validator,
        WebsiteValidator $websiteValidator,
        TierPriceValidator $tierPriceValidator,
        ConsoleOutput $output,
        ImportExportHelper $helper,
        ProductMetadataInterface $productMetadata,
        CacheInterface $cache
    ) {
        $version = $productMetadata->getVersion();
        if (version_compare($version, '2.4.2', '<')) {
            parent::__construct(
                $context->getJsonHelper(),
                $context->getImportExportData(),
                $context->getDataSourceModel(),
                $context->getConfig(),
                $context->getResource(),
                $context->getResourceHelper(),
                $context->getStringUtils(),
                $context->getErrorAggregator(),
                $dateTime,
                $resourceFactory,
                $productModel,
                $catalogHelper,
                $storeResolver,
                $importProduct,
                $validator,
                $websiteValidator,
                $tierPriceValidator
            );
        } else {
            parent::__construct(
                $context->getJsonHelper(),
                $context->getImportExportData(),
                $context->getDataSourceModel(),
                $context->getResource(),
                $context->getResourceHelper(),
                $context->getErrorAggregator(),
                $dateTime,
                $resourceFactory,
                $productModel,
                $catalogHelper,
                $storeResolver,
                $importProduct,
                $validator,
                $websiteValidator,
                $tierPriceValidator
            );
        }

        $this->setSerializer($context->getSerializer());
        $this->productMetadata = $productMetadata;
        $this->_logger = $context->getLogger();
        $this->output = $output;
        $this->_debugMode = $helper->getDebugMode();
        $this->cache = $cache;

        foreach ($this->messageTemplates as $errorCode => $message) {
            $this->addMessageTemplate($errorCode, $message);
        }
    }

    public function validateRow(array $rowData, $rowNum)
    {
        if (!isset($this->_validatedRows[$rowNum])) {
            $this->_processedRowsCount++;
            $this->_processedEntitiesCount++;

            if (parent::validateRow($rowData, $rowNum)) {
                $sku = $rowData[static::COL_SKU];
                $website = $rowData[static::COL_TIER_PRICE_WEBSITE];
                $group = $rowData[static::COL_TIER_PRICE_CUSTOMER_GROUP];
                $qty = $rowData[static::COL_TIER_PRICE_QTY];

                if (isset($this->checkDuplicates[$sku][$website][$group][$qty])) {
                    $this->addRowError(
                        RowValidatorInterface::ERROR_DUPLICATE_UNIQUE_ATTRIBUTE,
                        $rowNum
                    );
                }
                $this->checkDuplicates[$sku][$website][$group][$qty] = true;
            }
        }

        return !$this->getErrorAggregator()->isRowInvalid($rowNum);
    }

    /**
     * @param array $prices
     * @param string $table
     * @return $this
     * @throws Exception
     */
    protected function processCountExistingPrices($prices, $table)
    {
        $oldSkus = $this->retrieveOldSkus();
        $existProductIds = array_intersect_key($oldSkus, $prices);
        if (!count($existProductIds)) {
            return $this;
        }

        $tableName = $this->_resourceFactory->create()->getTable($table);
        $productEntityLinkField = $this->getProductEntityLinkField();
        $existingPrices = $this->_connection->fetchAll(
            $this->_connection->select()->from(
                $tableName,
                [$productEntityLinkField, 'all_groups', 'customer_group_id', 'qty']
            )->where(
                $productEntityLinkField . ' IN (?)',
                $existProductIds
            )
        );

        foreach ($existingPrices as $existingPrice) {
            foreach ($oldSkus as $sku => $productId) {
                if ($existingPrice[$productEntityLinkField] == $productId && isset($prices[$sku])) {
                    $this->incrementCounterUpdated($prices[$sku], $existingPrice);
                }
            }
        }

        return $this;
    }

    /**
     * @return string
     * @throws Exception
     */
    private function getProductEntityLinkField()
    {
        if (!$this->productEntityLinkField) {
            $this->productEntityLinkField = $this->getMetadataPool()
                ->getMetadata(ProductInterface::class)
                ->getLinkField();
        }

        return $this->productEntityLinkField;
    }

    /**
     * @return $this
     * @throws Exception
     */
    protected function saveAndReplaceAdvancedPrices()
    {
        $behavior = $this->getBehavior();
        if (Import::BEHAVIOR_REPLACE == $behavior) {
            $this->_cachedSkuToDelete = null;
        }
        $listSku = [];
        $tierPrices = [];
        $bunchSize = 0;
        $skusPerWebsite = [];
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $bunchSize = $bunchSize ?: count($bunch);
            foreach ($bunch as $rowNum => $rowData) {
                $rowData = $this->joinIdenticalyData($rowData);
                if (!$this->validateRow($rowData, $rowNum)) {
                    $this->addLogWriteln(
                        __('price from sku: %1 is not validated', $rowData[self::COL_SKU]),
                        $this->output,
                        'info'
                    );
                    continue;
                }
                $time = explode(" ", microtime());
                $startTime = $time[0] + $time[1];
                $sku = $rowData[self::COL_SKU];
                $rowData = $this->customChangeData($rowData);
                if (!$this->validateRow($rowData, $rowNum)) {
                    $this->addRowError(RowValidatorInterface::ERROR_SKU_IS_EMPTY, $rowNum);
                    continue;
                }
                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                    continue;
                }
                $rowSku = $rowData[self::COL_SKU];
                $listSku[] = $rowSku;
                if (!empty($rowData[self::COL_TIER_PRICE_WEBSITE])) {
                    $websiteId = $this->getWebsiteId($rowData[self::COL_TIER_PRICE_WEBSITE]);
                    if (!empty($websiteId)) {
                        $skusPerWebsite[$websiteId][] = $sku;
                    }
                    $array = [
                        'all_groups' => $rowData[self::COL_TIER_PRICE_CUSTOMER_GROUP] == self::VALUE_ALL_GROUPS,
                        'customer_group_id' => $this->getCustomerGroupId(
                            $rowData[self::COL_TIER_PRICE_CUSTOMER_GROUP]
                        ),
                        'qty' => $rowData[self::COL_TIER_PRICE_QTY],
                        'value' => $rowData[self::COL_TIER_PRICE],
                        'website_id' => $websiteId
                    ];
                    if (isset($rowData[self::COL_TIER_PRICE_TYPE])) {
                        switch ($rowData[self::COL_TIER_PRICE_TYPE]) {
                            case self::TIER_PRICE_TYPE_FIXED:
                                $array['value'] = $rowData[self::COL_TIER_PRICE];
                                $array['percentage_value'] = null;
                                break;
                            case self::TIER_PRICE_TYPE_PERCENT:
                                $array['value'] = 0;
                                $array['percentage_value'] = $rowData[self::COL_TIER_PRICE];
                                break;
                        }
                    }
                    $tierPrices[$rowSku][] = $array;
                }
                $time = explode(" ", microtime());
                $endTime = $time[0] + $time[1];
                $totalTime = $endTime - $startTime;
                $totalTime = round($totalTime, 5);
                $this->addLogWriteln(__('price from sku: %1 .... %2s', $sku, $totalTime), $this->output, 'info');
            }
            $this->getEntities($listSku);

            if ($behavior == Import::BEHAVIOR_APPEND) {
                $this->processCountExistingPrices($tierPrices, self::TABLE_TIER_PRICE)
                    ->processCountNewPrices($tierPrices);

                $this->saveProductPrices($tierPrices, self::TABLE_TIER_PRICE);
                if ($listSku) {
                    $this->setUpdatedAt($listSku);
                }
            }

            if ($behavior == Import::BEHAVIOR_REPLACE) {
                if ($listSku) {
                    $this->processCountNewPrices($tierPrices);
                    $deleteItems = true;
                    foreach ($skusPerWebsite as $websiteId => $skus) {
                        $uniqueListSku = array_unique($skus);
                        $cachedProductSku = $this->loadClearedProductsSku();
                        $clearedProductsSku = $cachedProductSku[$websiteId] ?? [];
                        foreach (array_chunk($uniqueListSku, $bunchSize) as $bunchDelete) {
                            $productsForDelete = array_diff($bunchDelete, $clearedProductsSku);
                            if ($productsForDelete) {
                                if ($this->deleteProductTierPricesPerWebsite(
                                    $productsForDelete,
                                    $websiteId,
                                    self::TABLE_TIER_PRICE
                                )) {
                                    $cachedProductSku[$websiteId] = array_merge($clearedProductsSku, $uniqueListSku);
                                    $this->saveClearedProductsSku($cachedProductSku);
                                } else {
                                    $deleteItems = false;
                                }
                            }
                        }
                    }
                    if ($deleteItems) {
                        foreach (array_chunk($tierPrices, $bunchSize, true) as $bunchTierPrices) {
                            $this->saveProductPrices($bunchTierPrices, self::TABLE_TIER_PRICE);
                        }
                        $this->setUpdatedAt($listSku);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Delete the tier prices per website
     *
     * @param array $productsForDelete
     * @param $websiteId
     * @param $table
     * @return bool
     * @throws Exception
     */
    protected function deleteProductTierPricesPerWebsite(array $productsForDelete, $websiteId, $table)
    {
        $tableName = $this->_resourceFactory->create()->getTable($table);
        $productEntityLinkField = $this->getProductEntityLinkField();
        if ($tableName && !empty($productsForDelete)) {
                    $productIds = $this->_connection->fetchCol(
                        $this->_connection->select()
                            ->from(['cpe' => $this->_catalogProductEntity], $productEntityLinkField)
                            ->join(['cpw' => 'catalog_product_website'], 'cpe.entity_id = cpw.product_id')
                            ->where('sku IN (?)', $productsForDelete)
                            ->where('cpw.website_id IN (?)', $websiteId)
                    );
            if (!empty($productIds)) {
                try {
                    $condition = $this->_connection->quoteInto($productEntityLinkField . ' IN (?)', $productIds) .
                        ' AND ' . $this->_connection->quoteInto(TierPrice::WEBSITE_ID . ' IN (?)', $websiteId);
                    $this->countItemsDeleted += $this->_connection->delete(
                        $tableName,
                        $condition
                    );
                    return true;
                } catch (\Exception $e) {
                    return false;
                }
            } else {
                $this->addRowError(ValidatorInterface::ERROR_SKU_IS_EMPTY, 0);
                return false;
            }
        }
        return false;
    }

    /**
     * @param $skuList
     */
    protected function saveClearedProductsSku($skuList)
    {
        if (is_array($skuList)) {
            $skuList = $this->serializer->serialize($skuList);
        }

        $this->cache->save(
            $skuList,
            static::CLEARED_PRODUCTS_CACHE_ID . ($this->_parameters['job_id'] ?? ''),
            ['fire_bear_import_cache'],
            3600 * 24
        );
    }

    /**
     * @return array
     */
    protected function loadClearedProductsSku()
    {
        $skuList = $this->cache->load(static::CLEARED_PRODUCTS_CACHE_ID . ($this->_parameters['job_id'] ?? ''));
        if ($skuList) {
            return $this->serializer->unserialize($skuList) ?? [];
        }
        return [];
    }

    /**
     * @param $listSku
     * @throws Exception
     */
    protected function getEntities($listSku)
    {
        $this->entityProducts = $this->_connection->fetchAll(
            $this->_connection->select()->from(
                $this->_catalogProductEntity,
                ['sku', $this->getProductEntityLinkField()]
            )->where('sku in(?)', $listSku)
        );
    }

    /**
     * @param $field
     * @return array
     */
    protected function getEntity($field)
    {
        $array = [];
        if (!empty($this->entityProducts)) {
            foreach ($this->entityProducts as $value) {
                $array[] = $value[$field];
            }
        }

        return $array;
    }

    /**
     * @return array
     * @throws Exception
     */
    protected function retrieveOldSkus()
    {
        $select = $this->_connection->select()->from(
            $this->_catalogProductEntity,
            ['sku', $this->getProductEntityLinkField()]
        );
        if ($skus = $this->getEntity('sku')) {
            $select->where('sku in(?)', $this->getEntity('sku'));
        }
        $this->_oldSkus = $this->_connection->fetchPairs(
            $select
        );
        return $this->_oldSkus;
    }

    /**
     * @return $this|MagentoAdvancedPricing
     * @throws LocalizedException
     */
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
                $this->_dataSourceModel->saveBunches(
                    $this->getEntityTypeCode(),
                    $this->getBehavior(),
                    $jobId,
                    $file,
                    $bunchRows
                );
                $bunchRows = $nextRowBackup;
                $currentDataSize = strlen($this->getSerializer()->serialize($bunchRows));
                $startNewBunch = false;
                $nextRowBackup = [];
            }
            if ($source->valid()) {
                try {
                    $rowData = $source->current();
                } catch (InvalidArgumentException $e) {
                    $this->addRowError($e->getMessage(), $this->_processedRowsCount);
                    $this->_processedRowsCount++;
                    $source->next();
                    continue;
                }

                $this->_processedRowsCount++;
                $rowData = $this->customBunchesData($rowData);
                $rowSize = strlen($this->getSerializer()->serialize($rowData));

                $isBunchSizeExceeded = $bunchSize > 0 && count($bunchRows) >= $bunchSize;

                if ($currentDataSize + $rowSize >= $maxDataSize || $isBunchSizeExceeded) {
                    $startNewBunch = true;
                    $nextRowBackup = [$source->key() => $rowData];
                } else {
                    $bunchRows[$source->key()] = $rowData;
                    $currentDataSize += $rowSize;
                }

                $source->next();
            }
        }

        return $this;
    }

    /**
     * Retrieve All Fields Source
     *
     * @return array
     */
    public function getAllFields()
    {
        return $this->validColumnNames;
    }
}
