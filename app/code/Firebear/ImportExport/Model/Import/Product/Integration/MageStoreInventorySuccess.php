<?php
declare(strict_types = 1);

/**
 * MageStoreInventorySuccess
 *
 * @copyright Copyright © 2019 Firebear Studio. All rights reserved.
 * @author    fbeardev@gmail.com
 */


namespace Firebear\ImportExport\Model\Import\Product\Integration;

use Exception;
use Firebear\ImportExport\Model\Import\Product;
use Firebear\ImportExport\Model\ResourceModel\Import\Data as ResourceModelData;
use Magento\CatalogImportExport\Model\Import\Product\SkuProcessor;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Magestore\InventorySuccess\Api\StockActivity\StockChangeInterface as MageStoreStockInterface;
use Magestore\InventorySuccess\Api\Warehouse\WarehouseRepositoryInterface as MageStoreWarehouseRepositoryInterface;
use Magestore\InventorySuccess\Api\Warehouse\WarehouseStockRepositoryInterface as MagWarehouseStockRepositoryInterface;
use Magestore\InventorySuccess\Model\StockActivity\StockChange;
use Magestore\InventorySuccess\Model\Warehouse;
use Magestore\InventorySuccess\Model\Warehouse\WarehouseRepository;
use Magestore\InventorySuccess\Model\Warehouse\WarehouseStockRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class MageStoreInventorySuccess extends AbstractIntegration
{
    /** @var string */
    const MODULE_KEY = 'Magestore_InventorySuccess';

    private $stockChange;

    private $mageWarehouse;

    private $mageWarehouseStockRepo;

    public function __construct(
        ObjectManager $objectManager,
        ResourceModelData $_dataSourceModel,
        ConsoleOutput $output,
        LoggerInterface $logger,
        ResourceConnection $resource,
        SkuProcessor $skuProcessor,
        ProductMetadataInterface $productMetadata,
        Manager $manager
    ) {
        parent::__construct(
            $objectManager,
            $_dataSourceModel,
            $output,
            $logger,
            $resource,
            $skuProcessor,
            $productMetadata,
            $manager
        );
        if ($manager->isEnabled(self::MODULE_KEY)) {
            if (class_exists(StockChange::class)) {
                $this->stockChange = $objectManager->create(MageStoreStockInterface::class);
            }
            if (class_exists(WarehouseRepository::class)) {
                $this->mageWarehouse = $objectManager->create(MageStoreWarehouseRepositoryInterface::class);
            }
            if (class_exists(WarehouseStockRepository::class)) {
                $this->mageWarehouseStockRepo = $objectManager->get(MagWarehouseStockRepositoryInterface::class);
            }
        }
    }

    /**
     * @param string|bool $verbosity
     *
     * @return mixed
     */
    public function importData($verbosity = false)
    {
        if ($verbosity) {
            $this->getOutput()->setVerbosity($verbosity);
        }
        $this->addLogWriteln(__('MageStore Inventory Integration'), $this->getOutput());
        $this->_construct();
        try {
            while ($bunch = $this->getDataSourceModel()->getNextBunch()) {
                foreach ($bunch as $rowData) {
                    $rowData = $this->customChangeData($rowData);
                    if (isset($rowData[Product::COL_SKU])) {
                        $productIdFromSku = (int)$this->getProductId($rowData[Product::COL_SKU]);
                        $this->addLogWriteln(
                            __('--------Start Update Stock for product %1 ----------', $rowData[Product::COL_SKU]),
                            $this->getOutput(),
                            'info'
                        );
                        $this->updateMageStoreWarehouse($rowData, $productIdFromSku);
                        $this->addLogWriteln(
                            __('--------End Update Stock for product %1 ----------', $rowData[Product::COL_SKU]),
                            $this->getOutput(),
                            'info'
                        );
                    }
                }
            }
        } catch (Exception $e) {
            $this->addLogWriteln($e->getMessage(), $this->getOutput(), 'error');
        }
    }

    /**
     * @param array $rowData
     * @param int $productId
     * @return $this
     * @throws NoSuchEntityException
     */
    protected function updateMageStoreWarehouse(array $rowData, int $productId)
    {
        foreach ($rowData as $attrCode => $attrValue) {
            if (preg_match('/^(magestore\|).+/', $attrCode)) {
                $wareHouseData = explode('|', $attrCode);
                $warehouseCode = '';
                $warehouseAction = 'update';
                foreach ($wareHouseData as $wValue) {
                    $val = explode(':', $wValue);
                    if ($val[0] === 'code') {
                        $warehouseCode = $val[1];
                    }
                    if ($val[0] === 'action') {
                        $warehouseAction = $val[1];
                    }
                }
                $wData = $this->getMageWarehouse()->get($warehouseCode);
                $warehouseProductData = $this->getMageWarehouseStockRepo()->getWarehouseStockBySku(
                    $wData->getWarehouseId(),
                    $rowData[Product::COL_SKU]
                );

                if ($warehouseProductData->isEmpty() && $warehouseAction == 'add' && $productId) {
                    $wareHouseModel = $this->getObjectManager()->get(Warehouse::class);
                    $wareHouseModel->createAdjustment($wData->getWarehouseId(), [$productId]);
                } else {
                    $warehouseAction = 'update';
                }

                if ($productId && $wData->getWarehouseId()) {
                    if ($warehouseAction === 'update') {
                        $this->getStockChange()->update($wData->getWarehouseId(), $productId, $attrValue);
                    } elseif ($warehouseAction === 'increase') {
                        $this->getStockChange()->increase($wData->getWarehouseId(), $productId, $attrValue);
                    } elseif ($warehouseAction === 'decrease') {
                        $this->getStockChange()->decrease($wData->getWarehouseId(), $productId, $attrValue);
                    }
                }
            }
        }
        return $this;
    }

    /**
     * @return MageStoreStockInterface
     */
    private function getStockChange()
    {
        return $this->stockChange;
    }

    /**
     * @return MageStoreWarehouseRepositoryInterface
     */
    private function getMageWarehouse()
    {
        return $this->mageWarehouse;
    }

    /**
     * @return MagWarehouseStockRepositoryInterface
     */
    private function getMageWarehouseStockRepo()
    {
        return $this->mageWarehouseStockRepo;
    }
}
