<?php
declare(strict_types=1);

namespace Firebear\ImportExport\Setup\Operations;

use Magento\Cms\Model\ResourceModel\Block;
use Magento\Cms\Model\ResourceModel\Page;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Class CreateCmsEntityTypes
 * @package Firebear\ImportExport\Setup\Operations
 */
class CreateCmsEntityTypes
{
    /**
     * @var EavSetup
     */
    private $eavSetup;

    /**
     * CreateCmsEntityTypes constructor
     *
     * @param EavSetup $eavSetup
     */
    public function __construct(
        EavSetup $eavSetup
    ) {
        $this->eavSetup = $eavSetup;
    }

    /**
     * Create CMS Page & Block Entity Types
     *
     * @param ModuleDataSetupInterface $setup
     */
    public function execute(
        ModuleDataSetupInterface $setup
    ) {
        $setup->startSetup();
        $this->createCmsBlockEntityType($setup);
        $this->createCmsPageEntityType($setup);
        $setup->endSetup();
    }

    /**
     * Create CMS Page Entity Type
     *
     * @param ModuleDataSetupInterface $setup
     */
    private function createCmsBlockEntityType(
        ModuleDataSetupInterface $setup
    ) {
        $this->eavSetup->addEntityType(
            'cms_page',
            [
                'entity_model' => Page::class,
                'attribute_model' => null,
                'entity_table' => 'cms_page'
            ]
        );
    }

    /**
     * Create CMS Block Entity Type
     *
     * @param ModuleDataSetupInterface $setup
     */
    private function createCmsPageEntityType(
        ModuleDataSetupInterface $setup
    ) {
        $this->eavSetup->addEntityType(
            'cms_block',
            [
                'entity_model' => Block::class,
                'attribute_model' => null,
                'entity_table' => 'cms_block'
            ]
        );
    }
}
