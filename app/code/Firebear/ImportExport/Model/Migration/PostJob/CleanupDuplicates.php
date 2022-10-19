<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration\PostJob;

use Firebear\ImportExport\Model\Migration\Config;
use Firebear\ImportExport\Model\Migration\DbConnection;
use Firebear\ImportExport\Model\Migration\PostJobInterface;

class CleanupDuplicates implements PostJobInterface
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
     * @var array
     */
    protected $fields;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param DbConnection $connector
     * @param string $table
     * @param Config $config
     * @param array $fields
     */
    public function __construct(
        DbConnection $connector,
        string $table,
        Config $config,
        array $fields
    ) {
        $this->connector = $connector;
        $this->table = $table;
        $this->config = $config;
        $this->fields = $fields;
    }

    /**
     * @inheritdoc
     */
    public function job()
    {
        $destination = $this->connector->getDestinationChannel();

        $fields = implode(', ', $this->fields);
        $tableName = $this->config->getM2Prefix() . $this->table;

        $sql = <<<SQL
SELECT
    *, count(*)
FROM {$tableName}
GROUP BY {$fields}
HAVING count(*) > 1;
SQL;

        $rows = $destination->query($sql)->fetchAll();

        foreach ($rows as $row) {
            $fields = array_keys($row);

            $primaryKeyField = $fields[0];
            $primaryKeyValue = $row[$primaryKeyField];

            $destination->delete(
                $this->config->getM2Prefix() . $this->table,
                "{$primaryKeyField} = {$primaryKeyValue}"
            );
        }
    }
}
