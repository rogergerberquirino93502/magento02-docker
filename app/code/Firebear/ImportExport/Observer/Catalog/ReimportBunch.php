<?php

namespace Firebear\ImportExport\Observer\Catalog;

use Magento\Framework\App\ProductMetadataInterface;

/**
 * Class ReimportBunch
 * @package Firebear\ImportExport\Observer\Catalog
 */
class ReimportBunch implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Firebear\ImportExport\Model\Catalog\UpdateCatalogBulk
     */
    protected $bulkUpdater;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * ReimportBunch constructor.
     * @param \Firebear\ImportExport\Model\Catalog\UpdateCatalogBulk $updater
     * @param ProductMetadataInterface $productMetadata
     */
    public function __construct(
        \Firebear\ImportExport\Model\Catalog\UpdateCatalogBulk $updater,
        ProductMetadataInterface $productMetadata
    ) {
        $this->bulkUpdater = $updater;
        $this->productMetadata = $productMetadata;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $versionNoAmqp = !version_compare($this->productMetadata->getVersion(), '2.3.3', '>=');
        if ($versionNoAmqp) {
            return;
        }
        $bunch = $observer->getEvent()->getBunch();
        $this->bulkUpdater->execute($bunch);
    }
}
