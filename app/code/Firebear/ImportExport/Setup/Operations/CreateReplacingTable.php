<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Setup\Operations;

use Firebear\ImportExport\Api\Data\JobReplacingInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * Class CreateReplacingTable
 * @package Firebear\ImportExport\Setup\Operations
 */
class CreateReplacingTable
{
    /**
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    public function execute(SchemaSetupInterface $setup)
    {
        $installer = $setup;
        $installer->startSetup();
        $installer->getConnection()->createTable(
            $this->makeTable($installer)
        );
        $installer->endSetup();
    }

    /**
     * @param SchemaSetupInterface $installer
     * @return Table
     * @throws \Zend_Db_Exception
     */
    private function makeTable(SchemaSetupInterface $installer)
    {
        $table = 'firebear_import_job_replacing';
        return $installer->getConnection()->newTable(
            $installer->getTable($table)
        )->addColumn(
            JobReplacingInterface::ENTITY_ID,
            Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Entity Id'
        )->addColumn(
            JobReplacingInterface::JOB_ID,
            Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Job Id'
        )->addColumn(
            JobReplacingInterface::ATTRIBUTE_CODE,
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Magento Attribute Code'
        )->addColumn(
            JobReplacingInterface::TARGET,
            Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => 0],
            'Target Option'
        )->addColumn(
            JobReplacingInterface::IS_CASE_SENSITIVE,
            Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => 0],
            'Is Case Sensitive'
        )->addColumn(
            JobReplacingInterface::FIND,
            Table::TYPE_TEXT,
            null,
            ['nullable' => false],
            'Find text'
        )->addColumn(
            JobReplacingInterface::ENTITY_TYPE,
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Entity type'
        )->addColumn(
            JobReplacingInterface::REPLACE,
            Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Replace text'
        )->addIndex(
            $installer->getIdxName($table, [JobReplacingInterface::JOB_ID]),
            [JobReplacingInterface::JOB_ID]
        )->addIndex(
            $installer->getIdxName($table, [JobReplacingInterface::ATTRIBUTE_CODE]),
            [JobReplacingInterface::ATTRIBUTE_CODE]
        )->addIndex(
            $installer->getIdxName($table, [JobReplacingInterface::TARGET]),
            [JobReplacingInterface::TARGET]
        )->addIndex(
            $installer->getIdxName(
                $table,
                [
                    JobReplacingInterface::JOB_ID,
                    JobReplacingInterface::ATTRIBUTE_CODE,
                    JobReplacingInterface::ENTITY_TYPE
                ],
                AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            [
                JobReplacingInterface::JOB_ID,
                JobReplacingInterface::ATTRIBUTE_CODE,
                JobReplacingInterface::ENTITY_TYPE
            ],
            ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
        )->addForeignKey(
            $installer->getFkName($table, JobReplacingInterface::JOB_ID, 'firebear_import_jobs', 'entity_id'),
            JobReplacingInterface::JOB_ID,
            $installer->getTable('firebear_import_jobs'),
            'entity_id',
            Table::ACTION_CASCADE
        )->setComment(
            'Import Find and Replace Data'
        );
    }
}
