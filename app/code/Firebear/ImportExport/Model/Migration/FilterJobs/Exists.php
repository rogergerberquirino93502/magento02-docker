<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration\FilterJobs;

use Firebear\ImportExport\Model\Migration\DbConnection;
use Firebear\ImportExport\Model\Migration\Config;

/**
 * @inheritdoc
 */
class Exists implements FilterJobsInterface
{
    /**
     * @var DbConnection
     */
    protected $dbConnection;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var string
     */
    protected $field = [];

    /**
     * @var array
     */
    protected $values = [];

    /**
     * @param DbConnection $dbConnection
     * @param Config $config
     * @param string $table
     * @param string $field
     */
    public function __construct(
        DbConnection $dbConnection,
        Config $config,
        string $table,
        string $field
    ) {
        $this->dbConnection = $dbConnection;
        $this->config = $config;
        $this->table = $table;
        $this->field = $field;
    }

    /**
     * @return array
     */
    protected function fetchValues()
    {
        $select = $this->dbConnection->getDestinationChannel()
            ->select()
            ->from($this->config->getM2Prefix() . $this->table, [$this->field]);

        $values = $this->dbConnection->getDestinationChannel()->fetchCol($select);

        return array_unique($values);
    }

    /**
     * @inheritdoc
     */
    public function apply($field, $select)
    {
        if (empty($this->values)) {
            $this->values = $this->fetchValues();
        }

        $select->where("{$field} IN (?)", $this->values);
    }
}
