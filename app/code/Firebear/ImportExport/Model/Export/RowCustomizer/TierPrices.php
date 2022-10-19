<?php
/**
 * TierPrices
 *
 * @copyright Copyright © 2020 Firebear Studio. All rights reserved.
 * @author    fbeardev@gmail.com
 */
declare(strict_types=1);

namespace Firebear\ImportExport\Model\Export\RowCustomizer;

use Exception;
use Firebear\ImportExport\Model\Export\Product;
use Firebear\ImportExport\Model\Import;
use Magento\CatalogImportExport\Model\Export\RowCustomizerInterface;
use Magento\CatalogImportExport\Model\Import\Product as ImportProduct;
use Magento\Customer\Model\ResourceModel\Group\Collection as CustomerGroupCollection;
use Magento\Framework\App\ResourceConnection as MagentoResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Export of tierPrices for the products
 * Class TierPrices
 * @package Firebear\ImportExport\Model\Export\RowCustomizer
 */
class TierPrices implements RowCustomizerInterface
{
    const TIER_PRICE_COLUMN = 'tier_prices';

    /**
     * @var array
     */
    protected $additionalColumns = [
        self::TIER_PRICE_COLUMN
    ];

    /**
     * @var array
     */
    protected $tierPriceData = [];

    /**
     * @var mixed
     */
    protected $collection;

    /**
     * @var Product
     */
    protected $entity;

    /**
     * @var MagentoResourceConnection
     */
    protected $resource;

    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @var WebsiteRepositoryInterface
     */
    protected $websiteRepository;

    /**
     * @var array
     */
    protected $websiteData = [];

    /**
     * @var CustomerGroupCollection
     */
    protected $groupCollection;

    /**
     * @var array
     */
    protected $customerGroup = [];

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * TierPrice constructor.
     * @param Product $entity
     * @param MagentoResourceConnection $resource
     * @param WebsiteRepositoryInterface $websiteRepository
     * @param CustomerGroupCollection $groupCollection
     * @param LoggerInterface $logger
     */
    public function __construct(
        Product $entity,
        MagentoResourceConnection $resource,
        WebsiteRepositoryInterface $websiteRepository,
        CustomerGroupCollection $groupCollection,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->entity = $entity;
        $this->websiteRepository = $websiteRepository;
        $this->groupCollection = $groupCollection;
        $this->logger = $logger;

        $this->init();
    }

    /**
     * @return $this
     */
    protected function init()
    {
        try {
            foreach ($this->websiteRepository->getList() as $website) {
                $this->websiteData[$website->getId()] = $website->getName();
            }

            $this->websiteData[0] = 'All';
            foreach ($this->groupCollection->toOptionArray() as $item) {
                $this->customerGroup[$item['value']] = $item['label'];
            }
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $this;
    }

    /**
     * @param mixed $collection
     * @param int[] $productIds
     * @return $this|mixed
     */
    public function prepareData($collection, $productIds)
    {
        if (empty($this->tierPriceData)) {
            $productTierPriceData = [];

            foreach ($this->getTierPrices($collection, $productIds) as $productId => $tierPrices) {
                if (is_array($tierPrices)) {
                    foreach ($tierPrices as $tierPrice) {
                        if (isset($tierPrice['all_groups']) && $tierPrice['all_groups'] == 1) {
                            $customerGroup = __('ALL GROUPS');
                        } else {
                            $customerGroup = $this->customerGroup[$tierPrice['customer_group_id']];
                        }

                        $websiteName = $this->websiteData[$tierPrice['website_id']];
                        $price = (isset($tierPrice['value']) && $tierPrice['value'] > 0) ? $tierPrice['value'] : 0;
                        $percentageValue = (isset($tierPrice['percentage_value'])
                            && $tierPrice['percentage_value'] > 0) ? $tierPrice['percentage_value'] : 0;

                        $str = $customerGroup . Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR .
                            $tierPrice['qty'] . Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR .
                            $price . Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR .
                            $percentageValue . Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR .
                            $websiteName;

                        $productTierPriceData[$productId][] = $str;
                    }
                }
            }

            if (!empty($productTierPriceData)) {
                foreach ($productTierPriceData as $productId => $tierVal) {
                    $result = [
                        self::TIER_PRICE_COLUMN => implode(
                            ImportProduct::PSEUDO_MULTI_LINE_SEPARATOR,
                            $tierVal
                        )
                    ];
                    $this->tierPriceData[$productId] = $result;
                }
            }
        }

        return $this;
    }

    /**
     * @param $collection
     * @param array $entityIds
     * @return array
     */
    private function getTierPrices($collection, array $entityIds)
    {
        if (empty($entityIds)) {
            return [];
        }
        $rowTierPrices = [];
        $productEntityField = $this->entity->_getProductEntityLinkField();
        if ($productEntityField == 'row_id') {
            $collectionEntityIdField = $collection->getIdFieldName();
            $collectionData = $collection->getData();
            if (empty($collectionData)) {
                return [];
            }
            $collectionEntityIds = array_column($collectionData, $collectionEntityIdField);
            $entityIds = array_column($collectionData, $productEntityField);
            $idPairs = array_combine($entityIds, $collectionEntityIds);
        }

        $select = $this->connection->select()->from(
            $this->resource->getTableName('catalog_product_entity_tier_price')
        )->where(
            "$productEntityField IN (?)",
            $entityIds
        );

        try {
            $stmt = $this->connection->query($select);

            while ($tierPriceRow = $stmt->fetch()) {
                if ($productEntityField == 'row_id') {
                    $productEntityId = $idPairs[$tierPriceRow[$productEntityField]];
                    $rowTierPrices[$productEntityId][] = $tierPriceRow;
                } else {
                    $rowTierPrices[$tierPriceRow[$productEntityField]][] = $tierPriceRow;
                }
            }
        } catch (Exception $exception) {
            $this->logger->critical($exception->getMessage());
        }

        return $rowTierPrices;
    }

    /**
     * @param array $columns
     * @return array|mixed
     */
    public function addHeaderColumns($columns)
    {
        return array_merge($columns, $this->additionalColumns);
    }

    /**
     * @param array $dataRow
     * @param int $productId
     * @return array|mixed
     */
    public function addData($dataRow, $productId)
    {
        if (!empty($this->tierPriceData[$productId])) {
            $dataRow = array_merge($dataRow, $this->tierPriceData[$productId]);
        }

        return $dataRow;
    }

    /**
     * @param array $additionalRowsCount
     * @param int $productId
     * @return array|mixed
     */
    public function getAdditionalRowsCount($additionalRowsCount, $productId)
    {
        if (!empty($this->tierPriceData[$productId])) {
            $additionalRowsCount = max($additionalRowsCount, count($this->tierPriceData[$productId]));
        }

        return $additionalRowsCount;
    }
}
