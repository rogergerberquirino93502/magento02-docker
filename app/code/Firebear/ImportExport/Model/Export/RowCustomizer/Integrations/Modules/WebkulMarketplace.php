<?php
/**
 * WebkulMarketplace
 *
 * @copyright Copyright © 2019 Firebear Studio. All rights reserved.
 * @author    fbeardev@gmail.com
 */

namespace Firebear\ImportExport\Model\Export\RowCustomizer\Integrations\Modules;

use Firebear\ImportExport\Model\Export\RowCustomizer\Integrations\AbstractExportIntegration;
use Webkul\Marketplace\Helper\Data as WebkulHelperData;

/**
 * Class WebkulMarketplace
 * @package Firebear\ImportExport\Model\Export\RowCustomizer\Integrations\Modules
 */
class WebkulMarketplace extends AbstractExportIntegration
{
    const VENDOR_ID = 'webkul_vendor_id';
    const MODULE_NAME = 'Webkul_Marketplace';

    protected $vendorData = [];
    private $webKulHelperManager;

    /**
     * Prepare data for export
     *
     * @param mixed $collection
     * @param int[] $productIds
     * @return mixed
     */
    public function prepareData($collection, $productIds)
    {
        if (empty($this->vendorData) && $this->isModuleEnabled()) {
            /**
             * @var WebkulHelperData
             */
            $this->webKulHelperManager = $this->getObjectManager()->get(WebkulHelperData::class);
            foreach ($productIds as $productId) {
                $this->vendorData[$productId] = $this->webKulHelperManager
                    ->getSellerProductDataByProductId($productId)
                    ->getFirstItem()
                    ->getId();
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
            $columns = array_merge(
                $columns,
                [static::VENDOR_ID]
            );
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
        if ($this->isModuleEnabled() && !empty($this->vendorData[$productId])) {
            $dataRow[static::VENDOR_ID] = $this->vendorData[$productId];
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
        if ($this->isModuleEnabled() && !empty($this->vendorData[$productId])) {
            $additionalRowsCount = max($additionalRowsCount, count($this->vendorData[$productId]));
        }
        return $additionalRowsCount;
    }
}
