<?php
/**
 * StockItemImporterInterface
 *
 * @copyright Copyright © 2021 Firebear Studio. All rights reserved.
 * @author    Firebear Studio <fbeardev@gmail.com>
 */
declare(strict_types=1);

namespace Firebear\ImportExport\Model\Import;

interface StockItemImporterInterface extends \Magento\CatalogImportExport\Model\StockItemImporterInterface
{
    /**
     * @param array $sourceData
     * @return $this
     */
    public function setSourceData(array $sourceData);

    /**
     * @return array
     */
    public function getSourceData();
}
