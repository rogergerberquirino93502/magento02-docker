<?php
declare(strict_types=1);

namespace Firebear\ImportExport\Plugin\Import;

use Firebear\ImportExport\Model\IsSingleSourceModeCacheProcess;
use Magento\CatalogImportExport\Model\StockItemImporterInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Module\Manager;
use Magento\Inventory\Model\SourceItem\Command\Handler\SourceItemsSaveHandler;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;

/**
 * Class SourceItemImporter
 * @package Firebear\ImportExport\Plugin\Import
 */
class SourceItemImporter
{
    /**
     * @var Manager
     */
    protected $moduleManager;

    /**
     * Source Item Interface Factory
     *
     * @var SourceItemInterfaceFactory $sourceItemFactory
     */
    private $sourceItemFactory;

    /**
     * Default Source Provider
     *
     * @var DefaultSourceProviderInterface $defaultSource
     */
    private $defaultSource;

    /**
     * @var SourceItemsSaveHandler
     */
    protected $sourceItemsSaveHandler;

    /**
     * @var IsSingleSourceModeCacheProcess
     */
    protected $isSingleSourceModeCacheProcess;

    /**
     * SourceItemImporter constructor.
     * @param SourceItemInterfaceFactory $sourceItemFactory
     * @param Manager $moduleManager
     * @param IsSingleSourceModeCacheProcess $isSingleSourceModeCacheProcess
     */
    public function __construct(
        SourceItemInterfaceFactory $sourceItemFactory,
        Manager $moduleManager,
        IsSingleSourceModeCacheProcess $isSingleSourceModeCacheProcess
    ) {
        $this->sourceItemFactory = $sourceItemFactory;
        $this->moduleManager = $moduleManager;
        $this->isSingleSourceModeCacheProcess = $isSingleSourceModeCacheProcess;
    }

    /**
     * @param StockItemImporterInterface $subject
     * @param $result
     * @param array $stockData
     * @return mixed
     */
    public function afterImport(
        StockItemImporterInterface $subject,
        $result,
        array $stockData
    ) {
        if ($this->moduleManager->isEnabled('Magento_Inventory') &&
            method_exists($subject, 'getSourceData')) {
            $sourceData = $subject->getSourceData();
            if (interface_exists(DefaultSourceProviderInterface::class)) {
                $this->defaultSource = ObjectManager::getInstance()
                    ->get(DefaultSourceProviderInterface::class);
            }
            if (class_exists(SourceItemsSaveHandler::class)) {
                $this->sourceItemsSaveHandler = ObjectManager::getInstance()
                    ->get(SourceItemsSaveHandler::class);
            }
            $sourceItems = [];
            foreach ($stockData as $sku => $stockDatum) {
                $sourceCode = $sourceData[$sku] ?? $this->defaultSource->getCode();
                $inStock = (isset($stockDatum['is_in_stock'])) ? ((int)$stockDatum['is_in_stock']) : 0;
                $qty = (isset($stockDatum['qty'])) ? $stockDatum['qty'] : 0;
                $sourceItem = $this->sourceItemFactory->create();
                $sourceItem->setSku((string)$sku);
                $sourceItem->setSourceCode($sourceCode);
                $sourceItem->setQuantity((float)$qty);
                $sourceItem->setStatus($inStock);
                $sourceItems[] = $sourceItem;
            }
            if (!empty($sourceItems)) {
                $this->isSingleSourceModeCacheProcess->enableCache();
                /** SourceItemInterface[] $sourceItems */
                $this->sourceItemsSaveHandler->execute($sourceItems);
                $this->isSingleSourceModeCacheProcess->disableCache();
            }
        }
        return $result;
    }
}
