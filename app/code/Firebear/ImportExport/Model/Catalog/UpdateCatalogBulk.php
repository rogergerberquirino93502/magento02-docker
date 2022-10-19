<?php
declare(strict_types=1);

namespace Firebear\ImportExport\Model\Catalog;

use Firebear\ImportExport\Model\ResourceModel\Catalog\CatalogEmail;
use Firebear\ImportExport\Model\ResourceModel\Catalog\Bunch;
use Magento\Framework\App\ObjectManager;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog as DotCatalog;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\App\ProductMetadataInterface;

/**
 * Class UpdateCatalogBulk
 * @package Firebear\ImportExport\Model\Catalog
 */
class UpdateCatalogBulk
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    private $dateTime;

    /**
     * @var Bunch
     */
    private $bunch;

    /**
     * @var CatalogEmail
     */
    protected $catalogEmail;

    /**
     * @var ModuleManager
     */
    protected $moduleManager;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * UpdateCatalogBulk constructor.
     * @param CatalogEmail $catalogEmail
     * @param DateTime $dateTime
     * @param ModuleManager $moduleManager
     * @param ProductMetadataInterface $productMetadata
     * @param Bunch $bunch
     */
    public function __construct(
        CatalogEmail $catalogEmail,
        DateTime $dateTime,
        ModuleManager $moduleManager,
        ProductMetadataInterface $productMetadata,
        Bunch $bunch
    ) {
        $this->dateTime = $dateTime;
        $this->catalogEmail = $catalogEmail;
        $this->bunch = $bunch;
        $this->moduleManager = $moduleManager;
        $this->productMetadata = $productMetadata;
    }

    /**
     * @param array $bunch
     */
    public function execute($bunch)
    {
        $isModuleEnabled = $this->moduleManager->isEnabled('Dotdigitalgroup_Email');
        if (!$isModuleEnabled) {
            return;
        }
        $bunchLimit = 500;
        $chunkBunches = array_chunk($bunch, $bunchLimit);

        foreach ($chunkBunches as $chunk) {
            $this->processBatch($chunk);
        }
    }

    /**
     * Process creates or updates a catalog with products
     * @param $bunch
     */
    private function processBatch($bunch)
    {
        $productIds = $this->bunch->getProductIdsBySkuInBunch($bunch);
        $existingProductIds = $this->catalogEmail->getExistingProductIds($productIds);

        $newEntryIds = array_diff($productIds, $existingProductIds);
        $createdAt = $this->dateTime->formatDate(true);

        $checkVersion234 = version_compare($this->productMetadata->getVersion(), '2.3.4', '>=');
        $checkVersion233 = version_compare($this->productMetadata->getVersion(), '2.3.4', '<');

        $newEntries = array_map(function ($id) use ($createdAt, $checkVersion234) {
            $result = [
                'product_id' => $id,
                'created_at' => $createdAt
            ];
            if ($checkVersion234) {
                $result['processed'] = 0;
            }

            return $result;
        }, $newEntryIds);

        $dotCatalog = ObjectManager::getInstance()->get(DotCatalog::class);
        if (!empty($newEntries)) {
            $dotCatalog->bulkProductImport($newEntries);
        }

        if (!empty($existingProductIds)) {
            if ($checkVersion233) {
                $dotCatalog->setModified($existingProductIds);
            } else {
                $dotCatalog->setUnprocessedByIds($existingProductIds);
            }
        }
    }
}
