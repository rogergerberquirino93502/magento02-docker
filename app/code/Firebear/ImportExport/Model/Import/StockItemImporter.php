<?php
/**
 * StockItemImporter
 *
 * @copyright Copyright © 2021 Firebear Studio. All rights reserved.
 * @author    Firebear Studio <fbeardev@gmail.com>
 */
declare(strict_types=1);

namespace Firebear\ImportExport\Model\Import;

use Magento\CatalogImportExport\Model\StockItemImporter as MagentoStockItemImporter;

class StockItemImporter extends MagentoStockItemImporter implements StockItemImporterInterface
{
    /**
     * @var array
     */
    private $sourceData = [];

    /**
     * @return array
     */
    public function getSourceData()
    {
        return $this->sourceData;
    }

    /**
     * @param array $sourceData
     * @return StockItemImporter|void
     */
    public function setSourceData(array $sourceData)
    {
        $this->sourceData = $sourceData;
        return $this;
    }
}
