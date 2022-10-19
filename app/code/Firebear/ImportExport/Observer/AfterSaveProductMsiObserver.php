<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Firebear\ImportExport\Model\Import\Product;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Firebear\ImportExport\Model\Import\SourceManager;

/**
 * Class AfterSaveProductMsiObserver
 * @package Firebear\ImportExport\Observer
 */
class AfterSaveProductMsiObserver implements ObserverInterface
{
    /**
     * @var SourceManager
     */
    protected $sourceManager;

    /**
     * AfterSaveProductMsiObserver constructor.
     * @param SourceManager $sourceManager
     */
    public function __construct(
        SourceManager $sourceManager
    ) {
        $this->sourceManager = $sourceManager;
    }

    /**
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Validation\ValidationException
     */
    public function execute(Observer $observer)
    {
        if (!$this->sourceManager->isEnableMsi()) {
            return;
        }
        $fieldNames = $this->sourceManager->getCoreFields($observer->getBunch());
        if (!$fieldNames) {
            return;
        }
        $sources = $this->sourceManager->getSourcesByFieldNames($fieldNames);
        $sourceBunch = [];
        $cache = [];
        foreach ($observer->getBunch() as $item) {
            foreach ($sources as $source) {
                if (empty($item[SourceManager::PREFIX . $source])
                    || !isset($item[SourceManager::PREFIX . $source . SourceManager::QTY_POSTFIX])
                    || !isset($item[SourceManager::PREFIX . $source . SourceManager::STATUS])) {
                    continue;
                }
                if (!empty($cache[$item[Product::COL_SKU]][$source])) {
                    continue;
                }
                $sourceBunch[] = [
                    SourceItemInterface::SKU => $item[Product::COL_SKU],
                    SourceItemInterface::SOURCE_CODE => $source,
                    SourceItemInterface::STATUS => $item[SourceManager::PREFIX . $source . SourceManager::STATUS],
                    SourceItemInterface::QUANTITY => $item[SourceManager::PREFIX . $source . SourceManager::QTY_POSTFIX]
                ];
                $cache[$item[Product::COL_SKU]][$source] = true;
            }
        }
        if (!$sourceBunch) {
            return;
        }
        $sourceBunch = $this->sourceManager->getSourceItemConvert()->convert($sourceBunch);
        $this->sourceManager->getSourceItemsSave()->execute($sourceBunch);
    }
}
