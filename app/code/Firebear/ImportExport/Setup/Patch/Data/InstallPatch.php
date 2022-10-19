<?php
declare(strict_types=1);

namespace Firebear\ImportExport\Setup\Patch\Data;

use Firebear\ImportExport\Setup\Operations\CreateCmsEntityTypes;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Firebear\ImportExport\Setup\Operations\EncryptPasswordFields;
use Firebear\ImportExport\Setup\Operations\UpdateFilePathWithPrefix;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
 * Class InstallPatch
 * @package Firebear\ImportExport\Setup\Patch\Data
 */
class InstallPatch implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;
    /**
     * @var CreateCmsEntityTypes
     */
    private $createCmsEntityTypes;
    /**
     * @var EncryptPasswordFields
     */
    private $encryptPasswordFields;
    /**
     * @var UpdateFilePathWithPrefix
     */
    private $updateFilePathWithPrefix;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CreateCmsEntityTypes $createCmsEntityTypes
     * @param EncryptPasswordFields $encryptPasswordFields
     * @param UpdateFilePathWithPrefix $updateFilePathWithPrefix
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CreateCmsEntityTypes $createCmsEntityTypes,
        EncryptPasswordFields $encryptPasswordFields,
        UpdateFilePathWithPrefix $updateFilePathWithPrefix
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->createCmsEntityTypes = $createCmsEntityTypes;
        $this->encryptPasswordFields = $encryptPasswordFields;
        $this->updateFilePathWithPrefix = $updateFilePathWithPrefix;
    }

    /**
     * @return InstallPatch|void
     * @throws \Magento\Framework\DB\FieldDataConversionException
     */
    public function apply()
    {
        $this->createCmsEntityTypes->execute($this->moduleDataSetup);
        $this->encryptPasswordFields->execute($this->moduleDataSetup);
        $this->updateFilePathWithPrefix->execute($this->moduleDataSetup);
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    public static function getVersion()
    {
        return "3.7.5";
    }
}
