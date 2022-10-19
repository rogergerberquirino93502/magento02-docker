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
class Sequence implements PostJobInterface
{
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
    protected $sequenceTable;

    /**
     * @var string
     */
    protected $fieldName;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param DbConnection $connector
     * @param Config $config
     * @param string $table
     * @param string $sequenceTable
     * @param string $fieldName
     */
    public function __construct(
        DbConnection $connector,
        Config $config,
        $table,
        $sequenceTable,
        $fieldName = 'entity_id'
    ) {
        $this->connector = $connector;
        $this->config = $config;
        $this->table = $table;
        $this->sequenceTable = $sequenceTable;
        $this->fieldName = $fieldName;
    }

    /**
     * @inheritdoc
     */
    public function job()
    {
        $select = $this->connector->getDestinationChannel()
            ->select()
            ->from($this->config->getM2Prefix() . $this->table, ['sequence_value' => $this->fieldName]);

        $ids = $this->connector->getDestinationChannel()->query($select)->fetchAll();

        $this->connector->getDestinationChannel()->query('SET FOREIGN_KEY_CHECKS = 0;');
        $this->connector->getDestinationChannel()->insertOnDuplicate(
            $this->config->getM2Prefix() . $this->sequenceTable,
            $ids
        );
        $this->connector->getDestinationChannel()->query('SET FOREIGN_KEY_CHECKS = 1;');
    }
}
