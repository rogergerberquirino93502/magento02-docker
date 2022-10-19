<?php

declare(strict_types=1);

/**
 * AbstractIntegration
 *
 * @copyright Copyright © 2018 Firebear Studio. All rights reserved.
 * @author    Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Product\Integration;

use Firebear\ImportExport\Model\Import\Product;
use Firebear\ImportExport\Model\ResourceModel\Import\Data as ResourceModelData;
use Firebear\ImportExport\Traits\General as GeneralTrait;
use Magento\CatalogImportExport\Model\Import\Product\SkuProcessor;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class AbstractIntegration
 * @package Firebear\ImportExport\Model\Import\Product\Integration
 */
abstract class AbstractIntegration implements IntegrationInterface
{
    use GeneralTrait;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var AdapterInterface
     */
    protected $connection;
    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var SkuProcessor
     */
    protected $skuProcessor;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;
    /**
     * @var $this
     */
    protected $oldSku;

    /**
     * DB data source model
     *
     * @var ResourceModelData
     */
    protected $_dataSourceModel;

    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * @var Product
     */
    private $adapter;

    /**
     * AbstractIntegration constructor.
     *
     * @param ObjectManager $objectManager
     * @param ResourceModelData $_dataSourceModel
     * @param ConsoleOutput $output
     * @param LoggerInterface $logger
     * @param ResourceConnection $resource
     * @param SkuProcessor $skuProcessor
     * @param ProductMetadataInterface $productMetadata
     * @param Manager $moduleManager
     */
    public function __construct(
        ObjectManager $objectManager,
        ResourceModelData $_dataSourceModel,
        ConsoleOutput $output,
        LoggerInterface $logger,
        ResourceConnection $resource,
        SkuProcessor $skuProcessor,
        ProductMetadataInterface $productMetadata,
        Manager $moduleManager
    ) {
        $this->objectManager = $objectManager;
        $this->_dataSourceModel = $_dataSourceModel;
        $this->output = $output;
        $this->_logger = $logger;
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->skuProcessor = $skuProcessor;
        $this->productMetadata = $productMetadata;
        $this->moduleManager = $moduleManager;
    }

    /**
     * @return ResourceModelData
     */
    public function getDataSourceModel(): ResourceModelData
    {
        return $this->_dataSourceModel;
    }

    /**
     * @param ResourceModelData $dataSourceModel
     *
     * @return ResourceModelData
     */
    public function setDataSourceModel(ResourceModelData $dataSourceModel): ResourceModelData
    {
        return $this->_dataSourceModel = $dataSourceModel;
    }

    /**
     * @return AdapterInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @return \Magento\Framework\App\ObjectManager
     */
    public function getObjectManager(): ObjectManager
    {
        return $this->objectManager;
    }

    /**
     * @param string|bool $verbosity
     *
     * @return mixed
     */
    abstract public function importData($verbosity = false);

    /**
     * @return Manager
     */
    public function getModuleManager(): Manager
    {
        return $this->moduleManager;
    }

    /**
     * @return Product
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * @param Product $adapter
     * @return Product
     */
    public function setAdapter(Product $adapter)
    {
        return $this->adapter = $adapter;
    }

    /**
     * Initialize old skus
     */
    protected function _construct(): void
    {
        $this->skuProcessor->reloadOldSkus();
        $this->oldSku = $this->skuProcessor->getOldSkus();
    }

    /**
     * @param string $sku
     *
     * @return mixed
     */
    protected function getProductId(string $sku)
    {
        return $this->getExistingSku($sku)['entity_id'];
    }

    /**
     * Get existing product data for specified SKU
     *
     * @param string $sku
     *
     * @return array
     */
    protected function getExistingSku(string $sku): array
    {
        if (version_compare($this->productMetadata->getVersion(), '2.2.0', '>=')) {
            $sku = strtolower($sku);
        }
        return $this->oldSku[$sku];
    }
}
