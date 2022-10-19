<?php
/**
 * MageStoreInventory
 *
 * @copyright Copyright © 2019 Firebear Studio. All rights reserved.
 * @author    fbeardev@gmail.com
 */

namespace Firebear\ImportExport\Model\Export\RowCustomizer\Integrations\Modules;

use Firebear\ImportExport\Model\Export\RowCustomizer\Integrations\AbstractExportIntegration;
use Magestore\InventorySuccess\Api\Warehouse\WarehouseStockRegistryInterface;

class MageStoreInventory extends AbstractExportIntegration
{
    /** @var string  */
    const MODULE_NAME = 'Magestore_InventorySuccess';

    /** @var array  */
    protected $mageStoreInvData = [];

    /** @var array  */
    protected $headerCol = [];

    private $warehouseData;

    /**
     * Prepare data for export
     *
     * @param mixed $collection
     * @param int[] $productIds
     * @return mixed
     */
    public function prepareData($collection, $productIds)
    {
        if (empty($this->mageStoreInvData) && $this->isModuleEnabled()) {
            /** @var WarehouseStockRegistryInterface */
            $this->warehouseData = $this->getObjectManager()->get(
                WarehouseStockRegistryInterface::class
            );
            foreach ($productIds as $productId) {
                $_warehouseStock = [];
                $warehouseData = $this->warehouseData->getStockWarehouses($productId)->getData();
                foreach ($warehouseData as $stockItemRow) {
                    unset(
                        $stockItemRow['item_id'],
                        $stockItemRow['product_id'],
                        $stockItemRow['low_stock_date'],
                        $stockItemRow['stock_id'],
                        $stockItemRow['website_id'],
                        $stockItemRow['stock_status_changed_auto']
                    );
                    $storeCode = 'magestore|code:' . $stockItemRow['warehouse_code'] . '|action:update';
                    $this->headerCol[] = $storeCode;
                    foreach (array_keys($stockItemRow) as $array_key) {
                        /** TODO: a future improvement if customer require more specific information about data */
//                        $this->headerCol[] = 'magestore|' . $array_key;
                        $this->headerCol[] = $array_key;
                    }
                    $_warehouseStock[$storeCode] = $stockItemRow;
                }
                $this->mageStoreInvData[$productId] = $_warehouseStock;
            }
        }
        return $this;
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
        if (!empty($this->mageStoreInvData[$productId]) && $this->isModuleEnabled()) {
            $totalQty = [];
            foreach ($this->mageStoreInvData[$productId] as $dataKey => $data) {
                $totalQty[] = $data['total_qty'];
                $dataRow[$dataKey] = $data['available_qty'];
                foreach ($data as $key => $datum) {
                    $dataRow[$key] = $datum;
                }
            }
            $dataRow['qty'] = array_sum($totalQty);
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
        if (!empty($this->mageStoreInvData[$productId]) && $this->isModuleEnabled()) {
            $additionalRowsCount = max($additionalRowsCount, count($this->mageStoreInvData[$productId]));
        }
        return $additionalRowsCount;
    }
}
