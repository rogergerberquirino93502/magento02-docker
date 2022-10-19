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
class CopyValueFromTheSameTable implements PostJobInterface
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
    protected $fieldName;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param DbConnection $connector
     * @param Config $config
     * @param string $table
     * @param string $fieldName
     */
    public function __construct(
        DbConnection $connector,
        Config $config,
        $table,
        $fieldName = 'attribute_group_name'
    ) {
        $this->connector = $connector;
        $this->config = $config;
        $this->table = $table;
        $this->fieldName = $fieldName;
    }

    /**
     * @inheritdoc
     */
    public function job()
    {
        $select = $this->connector->getDestinationChannel()
            ->select()
            ->from($this->config->getM2Prefix() . $this->table, ['attribute_group_name' => $this->fieldName]);

        $ids = $this->connector->getDestinationChannel()->query($select)->fetchAll();
        $ids = array_change_key_case($ids);
        $output = array_map(function ($value) {
            return str_replace(' ', '-', $value);
        }, $ids);

        $this->connector->getDestinationChannel()->query('SET FOREIGN_KEY_CHECKS = 0;');
        $this->connector->getDestinationChannel()->update(
            $this->config->getM2Prefix() . $this->table,
            ['attribute_group_code' => $output],
            'attribute_group_code IS NULL'
        );
        $this->connector->getDestinationChannel()->query('SET FOREIGN_KEY_CHECKS = 1;');
    }
}
