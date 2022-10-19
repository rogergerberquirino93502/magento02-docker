<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration\PostJob;

use Firebear\ImportExport\Model\Migration\Config;
use Firebear\ImportExport\Model\Migration\DbConnection;
use Firebear\ImportExport\Model\Migration\PostJobInterface;

/**
 * @package Firebear\ImportExport\Model\Migration\PostJob
 */
class SalesSequence implements PostJobInterface
{
    /**
     * Auto increment step size.
     *
     * @type int
     */
    const AUTO_INCREMENT_STEP = 1;

    /**
     * @var DbConnection
     */
    protected $connector;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var string
     */
    private $entityType;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param DbConnection $connector
     * @param Config $config
     * @param string $entityType
     * @param string $table
     */
    public function __construct(
        DbConnection $connector,
        Config $config,
        string $entityType,
        string $table
    ) {
        $this->connector = $connector;
        $this->config = $config;
        $this->table = $table;
        $this->entityType = $entityType;
    }

    /**
     * @inheritdoc
     */
    public function job()
    {
        $connection = $this->connector->getDestinationChannel();
        $sourceTableName = $connection->getTableName($this->config->getM2Prefix() . $this->table);
        $select = $connection->select()
            ->from($sourceTableName, ['store_id'])
            ->group('store_id');

        $data = $connection->query($select)->fetchAll();

        foreach ($data as $item) {
            $storeId = (int) $item['store_id'];
            $tableSequenceName = $this->getSequenceTable($storeId);

            if ($connection->isTableExists($tableSequenceName)) {
                $connection->truncateTable($tableSequenceName);
                $connection->query(sprintf(
                    'ALTER TABLE %s AUTO_INCREMENT = %s',
                    $tableSequenceName,
                    $this->getStartValue($storeId)
                ));
            } else {
                throw new \Zend_Db_Statement_Exception('Table ' . $tableSequenceName . ' does not exits.');
            }
        }
    }

    /**
     * Returns start auto increment value for store.
     *
     * @param int $storeId
     *
     * @return int
     */
    protected function getStartValue($storeId)
    {
        $connection = $this->connector->getDestinationChannel();
        $tableName = $connection->getTableName($this->config->getM2Prefix() . $this->table);
        $select = $connection->select();

        $fieldName = $connection->getAutoIncrementField($tableName);
        $select->from($tableName, new \Zend_Db_Expr(sprintf('MAX(%s)', $fieldName)))
            ->where('store_id = ?', $storeId);

        return (int) $connection->fetchOne($select) + static::AUTO_INCREMENT_STEP;
    }

    /**
     * Returns sequence table name.
     *
     * @param int $storeId
     *
     * @return string
     */
    protected function getSequenceTable($storeId)
    {
        return $this->connector->getDestinationChannel()->getTableName(
            sprintf(
                $this->config->getM2Prefix() . 'sequence_%s_%s',
                $this->entityType,
                $storeId
            )
        );
    }
}
