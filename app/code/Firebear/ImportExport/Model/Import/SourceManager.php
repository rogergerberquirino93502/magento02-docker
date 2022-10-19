<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import;

use Magento\Framework\App\ObjectManager;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryImportExport\Model\Import\SourceItemConvert;
use Magento\Framework\Module\Manager as ModuleManager;

/**
 * Class SourceManager
 * @package Firebear\ImportExport\Model\Import
 */
class SourceManager
{
    const PREFIX = 'msi_';
    const QTY_POSTFIX = '_qty';
    const STATUS = '_status';

    /**
     * @var mixed
     */
    protected $sourceItemsSave;

    /**
     * @var mixed
     */
    protected $sourceItemConvert;

    /**
     * @var ModuleManager
     */
    protected $moduleManager;

    /**
     * SourceManager constructor.
     * @param ModuleManager $moduleManager
     */
    public function __construct(ModuleManager $moduleManager)
    {
        $this->moduleManager = $moduleManager;
        if ($this->isEnableMsi()) {
            $this->sourceItemsSave = ObjectManager::getInstance()->get(SourceItemsSaveInterface::class);
            $this->sourceItemConvert = ObjectManager::getInstance()->get(SourceItemConvert::class);
        }
    }

    /**
     * @param array $bunch
     * @return array
     */
    public function getCoreFields(array $bunch): array
    {
        if (is_array($bunch) && count($bunch)) {
            return array_filter(array_keys(current($bunch)), function ($var) {
                return preg_match(
                    '/^' . self::PREFIX . '((?!' . self::QTY_POSTFIX . '|' . self::STATUS . ').)*$/',
                    $var
                );
            });
        } else {
            return [];
        }
    }

    /**
     * @param array $names
     * @return array
     */
    public function getSourcesByFieldNames(array $names): array
    {
        return array_map(
            function ($item) {
                return str_replace(SourceManager::PREFIX, '', $item);
            },
            $names
        );
    }

    /**
     * @return mixed
     */
    public function getSourceItemsSave()
    {
        return $this->sourceItemsSave ?? false;
    }

    /**
     * @return mixed
     */
    public function getSourceItemConvert()
    {
        return $this->sourceItemConvert ?? false;
    }

    /**
     * @return bool
     */
    protected function hasMsi(): bool
    {
        return interface_exists(SourceItemsSaveInterface::class)
            && class_exists(SourceItemConvert::class);
    }

    /**
     * @return bool
     */
    public function isEnableMsi(): bool
    {
        if (!$this->isEnableCoreMsiModules() || !$this->hasMsi()) {
            return false;
        }
        return true;
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
