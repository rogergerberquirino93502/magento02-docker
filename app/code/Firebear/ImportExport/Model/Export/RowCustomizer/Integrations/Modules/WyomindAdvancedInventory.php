<?php
/**
 * WyomindAdvancedInventory
 *
 * @copyright Copyright © 2019 Firebear Studio. All rights reserved.
 * @author    fbeardev@gmail.com
 */

namespace Firebear\ImportExport\Model\Export\RowCustomizer\Integrations\Modules;

use Exception;
use Firebear\ImportExport\Model\Export\RowCustomizer\Integrations\AbstractExportIntegration;
use Magento\Framework\DataObject;
use Wyomind\AdvancedInventory\Model\Stock as AdvancedInventoryStockModel;
use Wyomind\AdvancedInventory\Model\StockFactory as AdvancedInventoryStockModelFactory;
use Wyomind\PointOfSale\Model\PointOfSale;
use Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\Collection as PosCollection;
use Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\CollectionFactory as PosCollectionFactory;

/**
 * Class WyomindAdvancedInventory
 * @package Firebear\ImportExport\Model\Export\RowCustomizer\Integrations\Modules
 */
class WyomindAdvancedInventory extends AbstractExportIntegration
{
    protected $wyomindData = [];
    protected $headerCol = [];
    /**
     * @var AdvancedInventoryStockModel
     */
    private $advancedStockModelFactory;
    /**
     * @var PosCollection
     */
    private $posCollectionFactory;

    /**
     * Prepare data for export
     *
     * @param mixed $collection
     * @param int[] $productIds
     * @return mixed
     */
    public function prepareData($collection, $productIds)
    {
        if (empty($this->wyomindData) && $this->isModuleEnabled()) {
            /** @var AdvancedInventoryStockModel advancedStockModelFactory */
            $this->advancedStockModelFactory = $this->getObjectManager()
                ->create(AdvancedInventoryStockModelFactory::class);
            /** @var PosCollection posCollectionFactory */
            $this->posCollectionFactory = $this->getObjectManager()->create(PosCollectionFactory::class);
            try {
                foreach ($productIds as $productId) {
                    $_warehouseStock = [];
                    $pointOfSales = $this->getPosCollection();
                    $totalQty = [];
                    /** @var PointOfSale $pointOfSale */
                    foreach ($pointOfSales as $pointOfSale) {
                        /** @var DataObject $stocks */
                        $stocks = $this->getAdvancedStockModel()
                            ->getStockSettings($productId, null, [$pointOfSale->getPlaceId()]);
                        $getQuantity = 'getQuantity' . $pointOfSale->getPlaceId();
                        $storeCode = 'wyomind|id:' . $pointOfSale->getPlaceId() . '|field:qty|code:'
                            . $pointOfSale->getStoreCode();
                        $_warehouseStock[$storeCode] = $stocks->$getQuantity();
                        $this->headerCol[] = $storeCode;
                        $totalQty[] = $stocks->$getQuantity();
                    }
                    $this->wyomindData[$productId] = $_warehouseStock;
                    $this->wyomindData[$productId]['qty'] = array_sum($totalQty);
                }
            } catch (Exception $exception) {
            }
        }
        return $this;
    }

    /**
     * @return bool
     */
    protected function isModuleEnabled(): bool
    {
        return $this->getModuleManager()->isEnabled('Wyomind_AdvancedInventory')
            && $this->getModuleManager()->isEnabled('Wyomind_PointOfSale');
    }

    /**
     * @return PosCollection
     */
    private function getPosCollection(): PosCollection
    {
        return $this->posCollectionFactory->create();
    }

    /**
     * @return AdvancedInventoryStockModel
     */
    private function getAdvancedStockModel(): AdvancedInventoryStockModel
    {
        return $this->advancedStockModelFactory->create();
    }

    /**
     * Set headers columns
     *
     * @param array $columns
     * @return mixed
     */
    public function addHeaderColumns($columns)
    {
        if ($this->isModuleEnabled()) {
            $columns = array_merge($columns, array_unique($this->headerCol));
        }
        return $columns;
    }

    /**
     * Add data for export
     *
     * @param array $dataRow
     * @param int $productId
     * @return mixed
     */
    public function addData($dataRow, $productId)
    {
        if (!empty($this->wyomindData[$productId]) && $this->isModuleEnabled()) {
            foreach ($this->wyomindData[$productId] as $key => $data) {
                $dataRow[$key] = $data;
            }
        }
        return $dataRow;
    }

    /**
     * Calculate the largest links block
     *
     * @param array $additionalRowsCount
     * @param int $productId
     * @return mixed
     */
    public function getAdditionalRowsCount($additionalRowsCount, $productId)
    {
        if (!empty($this->wyomindData[$productId]) && $this->isModuleEnabled()) {
            $additionalRowsCount = max($additionalRowsCount, count($this->wyomindData[$productId]));
        }
        return $additionalRowsCount;
    }
}
