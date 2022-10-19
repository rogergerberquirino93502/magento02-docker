<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export\Product\FieldsPool;

use Firebear\ImportExport\Model\Export\Product\AdditionalFieldsInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface as SIInterface;
use Firebear\ImportExport\Model\ResourceModel\Catalog\Sources;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Module\Manager as ModuleManager;

/**
 * Class Msi
 * @package Firebear\ImportExport\Model\Export\Product\FieldsPool
 */
class Msi implements AdditionalFieldsInterface
{
    const PREFIX = 'msi_';
    const QTY_POSTFIX = '_qty';
    const STATUS_POSTFIX = '_status';

    protected $isSingleSourceMode;

    protected $getSourceItemsBySku;

    /**
     * @var Sources
     */
    protected $sources;

    /**
     * @var array
     */
    protected $sourceCodes;

    /**
     * @var ModuleManager
     */
    protected $moduleManager;

    /**
     * @var bool
     */
    protected $singleSourceMode;

    /**
     * Msi constructor.
     * @param Sources $sources
     * @param ModuleManager $moduleManager
     */
    public function __construct(
        Sources $sources,
        ModuleManager $moduleManager
    ) {
        $this->sources = $sources;
        $this->moduleManager = $moduleManager;
        if ($this->hasMsi() && $this->isEnableCoreMsiModules()) {
            $this->isSingleSourceMode = ObjectManager::getInstance()->get(IsSingleSourceModeInterface::class);
            $this->getSourceItemsBySku = ObjectManager::getInstance()->get(GetSourceItemsBySkuInterface::class);
        }
    }

    /**
     * @param array $rows
     * @return $this
     */
    public function addFields(array &$rows): self
    {
        if (!$this->isEnableMsi()) {
            return $this;
        }
        $skus = array_map(function ($item) {
            return current($item)[SIInterface::SKU];
        }, $rows);
        $sourceItems = $this->getSourceItems($skus);
        foreach ($rows as $prodId => $product) {
            foreach ($product as $storeId => $fields) {
                $sourceItemCodesCurrent = (!empty($sourceItems[$fields[SIInterface::SKU]])
                    && is_array($sourceItems[$fields[SIInterface::SKU]]))
                    ? array_keys($sourceItems[$fields[SIInterface::SKU]]) : [];
                foreach ($this->getSourceCodes() as $sourceCode) {
                    $rows[$prodId][$storeId][self::PREFIX . $sourceCode] = 0;
                    if (in_array($sourceCode, $sourceItemCodesCurrent)) {
                        $rows[$prodId][$storeId][self::PREFIX . $sourceCode] = 1;
                        $rows[$prodId][$storeId][self::PREFIX . $sourceCode . self::QTY_POSTFIX]
                            = $sourceItems[$fields[SIInterface::SKU]][$sourceCode][SIInterface::QUANTITY];
                        $rows[$prodId][$storeId][self::PREFIX . $sourceCode . self::STATUS_POSTFIX]
                            = $sourceItems[$fields[SIInterface::SKU]][$sourceCode][SIInterface::STATUS];
                    }
                }
            }
        }
        return $this;
    }

    /**
     * @param array $skus
     * @return array
     */
    protected function getSourceItems(array $skus): array
    {
        $return = [];
        foreach ($this->sources->getSourceItemsBySkus($skus) as $item) {
            $return[$item[SIInterface::SKU]][$item[SIInterface::SOURCE_CODE]] = $item;
        }
        return $return;
    }

    /**
     * @return array
     */
    protected function getSourceCodes(): array
    {
        if (!empty($this->sourceCodes)) {
            return $this->sourceCodes;
        }
        $this->sourceCodes = $this->sources->getSourceCodes();
        return $this->sourceCodes;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        if (!$this->isEnableMsi()) {
            return [];
        }
        $result = [];
        foreach ($this->getSourceCodes() as $sourceCode) {
            $result[] = self::PREFIX . $sourceCode;
            $result[] = self::PREFIX . $sourceCode . self::QTY_POSTFIX;
            $result[] = self::PREFIX . $sourceCode . self::STATUS_POSTFIX;
        }
        return $result;
    }

    /**
     * @return bool
     */
    protected function isEnableMsi()
    {
        if (!$this->isEnableCoreMsiModules()) {
            return false;
        }
        if ($this->singleSourceMode === null) {
            $this->singleSourceMode = $this->isSingleSourceMode->execute();
        }
        return $this->hasMsi() && !$this->singleSourceMode;
    }

    /**
     * @return bool
     */
    public function hasMsi(): bool
    {
        return interface_exists(IsSingleSourceModeInterface::class)
            && interface_exists(GetSourceItemsBySkuInterface::class);
    }

    /**
     * @return bool
     */
    protected function isEnableCoreMsiModules(): bool
    {
        return $this->moduleManager->isEnabled('Magento_Inventory') &&
            $this->moduleManager->isEnabled('Magento_InventoryCatalog') &&
            $this->moduleManager->isEnabled('Magento_InventoryImportExport') &&
            $this->moduleManager->isEnabled('Magento_InventoryCatalogApi') &&
            $this->moduleManager->isEnabled('Magento_InventoryApi');
    }
}
