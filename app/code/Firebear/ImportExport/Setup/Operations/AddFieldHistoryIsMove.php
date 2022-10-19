<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Setup\Operations;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Add field operation
 */
class AddFieldHistoryIsMove
{
    /**
     * Add field
     *
     * @param SchemaSetupInterface $setup
     * @return void
     */
    public function execute(SchemaSetupInterface $setup)
    {
        $setup->getConnection()->addColumn(
            $setup->getTable('firebear_export_history'),
            'is_moved',
            [
                'type' => Table::TYPE_SMALLINT,
                'nullable' => false,
                'unsigned' => true,
                'default' => '0',
                'comment' => 'Is Moved'
            ]
        );
    }
}
