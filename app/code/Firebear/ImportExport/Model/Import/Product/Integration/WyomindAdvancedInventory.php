<?php

declare(strict_types=1);

/**
 * WyomindInventory
 *
 * @copyright Copyright © 2019 Firebear Studio. All rights reserved.
 * @author    Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Product\Integration;

use Exception;
use Firebear\ImportExport\Model\Import\Product;
use Firebear\ImportExport\Model\ResourceModel\Import\Data as ResourceModelData;
use Magento\CatalogImportExport\Model\Import\Product\SkuProcessor;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Model\StockRegistry;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Wyomind\AdvancedInventory\Api\StockRepositoryInterface;
use Wyomind\AdvancedInventory\Model\Stock;
use Wyomind\AdvancedInventory\Model\StockFactory;
use Wyomind\AdvancedInventory\Model\StockRepository;
use Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\Collection as PosCollection;
use Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\CollectionFactory as PosCollectionFactory;

use function array_sum;
use function class_exists;

/**
 * Class WyomindAdvancedInventory
 * @package Firebear\ImportExport\Model\Import\Product\Integration
 */
class WyomindAdvancedInventory extends AbstractIntegration
{
    /** @var string */
    const MODULE_KEY = 'Wyomind_AdvancedInventory';

    /** @var string */
    const MODULE_KEY_1 = 'Wyomind_PointOfSale';

    /**
     * @var StockRegistry
     */
    protected $stockRegistry;

    /**
     * @var StockConfigurationInterface
     */
    protected $stockConfiguration;

    /** @var StockFactory */
    private $stockModelFactory;

    /** @var StockRepositoryInterface */
    private $stockRepoInterface;

    /** @var PosCollectionFactory */
    private $posCollectionFactory;

    /**
     * WyomindInventory constructor.
     *
     * @param ObjectManager $objectManager
     * @param ResourceModelData $_dataSourceModel
     * @param ConsoleOutput $output
     * @param LoggerInterface $logger
     * @param ResourceConnection $resource
     * @param SkuProcessor $skuProcessor
     * @param ProductMetadataInterface $productMetadata
     * @param StockRegistry $stockRegistry
     * @param StockConfigurationInterface $stockConfiguration
     */
    public function __construct(
        ObjectManager $objectManager,
        ResourceModelData $_dataSourceModel,
        ConsoleOutput $output,
        LoggerInterface $logger,
        ResourceConnection $resource,
        SkuProcessor $skuProcessor,
        ProductMetadataInterface $productMetadata,
        Manager $manager,
        StockRegistry $stockRegistry,
        StockConfigurationInterface $stockConfiguration
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

        $this->stockRegistry = $stockRegistry;
        $this->stockConfiguration = $stockConfiguration;
        if ($manager->isEnabled(self::MODULE_KEY) && $manager->isEnabled(self::MODULE_KEY_1)) {
            if (class_exists(Stock::class)) {
                $this->stockModelFactory = $objectManager->create(StockFactory::class);
            }
            if (class_exists(PosCollection::class)) {
                $this->posCollectionFactory = $objectManager->create(PosCollectionFactory::class);
            }
            if (class_exists(StockRepository::class)) {
                $this->stockRepoInterface = $objectManager->get(StockRepositoryInterface::class);
            }
        }
    }

    /**
     * @param bool $verbosity
     *
     * @return mixed|void
     */
    public function importData($verbosity = true)
    {
        if ($verbosity) {
            $this->getOutput()->setVerbosity($verbosity);
        }
        $this->addLogWriteln(__('Wyomind Inventory Integration'), $this->getOutput());
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
                        $this->updateWyomindAI($rowData, $productIdFromSku, $rowData[Product::COL_SKU]);
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
     * @param string $productSku
     */
    private function updateWyomindAI(array $rowData, int $productId, string $productSku): void
    {
        $totalQty = [];
        foreach ($rowData as $attrCode => $attrValue) {
            if (preg_match('/^(wyomind\|).+/', $attrCode)) {
                $wareHouseData = explode('|', $attrCode);
                $warehouseId = '';
                $field = 'qty';
                foreach ($wareHouseData as $wValue) {
                    $val = explode(':', $wValue);
                    if ($val[0] === 'id') {
                        $warehouseId = $val[1];
                    }
                    if ($val[0] === 'field') {
                        $field = $val[1];
                    }
                }

                $pos = $this->getPosCollection()->getPlace($warehouseId);
                if ($field === 'qty' && ($pos->count() > 0)) {
                    $pos = $pos->getFirstItem()->getData();
                    try {
                        $this->getStockInterface()
                            ->updateStock(
                                $productId,
                                $rowData['wyomind_multistock_enabled'] ?? 1,
                                $warehouseId,
                                $rowData['manage_stock'] ?? 1,
                                $attrValue,
                                $rowData['allow_backorders'] ?? 0,
                                $rowData['use_config_backorders'] ?? 0
                            );
                        $stock = $this->getStockModel()->getStockSettings($productId, false, [$warehouseId]);
                        $stockId = 'getStockId' . $warehouseId;
                        $data = [
                            'id' => $stock->$stockId(),
                            'item_id' => $stock->getItemId(),
                            'place_id' => $warehouseId,
                            'product_id' => $productId,
                            'quantity_in_stock' => $attrValue,
                        ];
                        if (!empty($data)) {
                            $totalQty[] = $attrValue;
                            $this->getStockModel()->load($data['id'])->setData($data)->save();
                            $this->addLogWriteln(
                                __('Stock Update for Warehouse %1 with qty %2', $pos['store_code'], $attrValue),
                                $this->getOutput(),
                                'info'
                            );
                        }
                    } catch (Exception $exception) {
                        $this->addLogWriteln(
                            $exception->getMessage(),
                            $this->getOutput(),
                            'error'
                        );
                    }
                }
            }
        }
        if (!empty($totalQty)) {
            try {
                $stockTable = $this->getConnection()->getTableName('cataloginventory_stock_item');
                $select = $this->getConnection()->select()
                    ->from($stockTable)
                    ->where('product_id = ?', $productId);
                $stockItemId = $this->getConnection()->fetchRow($select)['item_id'] ?? 0;
                if ($stockItemId > 0) {
                    $stockItem = $this->stockRegistry->getStockItem(
                        $productId,
                        $this->stockConfiguration->getDefaultScopeId()
                    );
                    $stockItem->setItemId($stockItemId)->setQty(array_sum($totalQty));
                    $this->stockRegistry->updateStockItemBySku($productSku, $stockItem);
                    $this->addLogWriteln(
                        __('update default stock table %1', $stockTable),
                        $this->getOutput(),
                        'info'
                    );
                }
            } /** @noinspection PhpRedundantCatchClauseInspection */ catch (CouldNotSaveException $exception) {
                $this->addLogWriteln(
                    $exception->getMessage(),
                    $this->getOutput(),
                    'error'
                );
            } catch (Exception $exception) {
                $this->addLogWriteln(
                    $exception->getMessage(),
                    $this->getOutput(),
                    'error'
                );
            }
        }
    }

    /**
     * @return PosCollection
     */
    private function getPosCollection(): PosCollection
    {
        return $this->posCollectionFactory->create();
    }

    /**
     * @return StockRepositoryInterface
     */
    private function getStockInterface(): StockRepositoryInterface
    {
        return $this->stockRepoInterface;
    }

    /**
     * @return Stock
     */
    private function getStockModel(): Stock
    {
        return $this->stockModelFactory->create();
    }
}
