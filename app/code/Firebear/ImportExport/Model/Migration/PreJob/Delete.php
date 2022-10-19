<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration\PreJob;

use Firebear\ImportExport\Model\Migration\Config;
use Firebear\ImportExport\Model\Migration\DbConnection;
use Firebear\ImportExport\Model\Migration\PreJobInterface;

/**
 * @inheritdoc
 */
class Delete implements PreJobInterface
{
    /**
     * @var DbConnection
     */
    protected $dbConnection;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var string
     */
    protected $cond;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param DbConnection $dbConnection
     * @param Config $config
     * @param string $table
     * @param string $cond
     */
    public function __construct(
        DbConnection $dbConnection,
        Config $config,
        string $table,
        string $cond
    ) {
        $this->dbConnection = $dbConnection;
        $this->config = $config;
        $this->table = $table;
        $this->cond = $cond;
    }

    /**
     * @inheritdoc
     */
    public function job()
    {
        $this->dbConnection->getDestinationChannel()->query('SET FOREIGN_KEY_CHECKS = 0;');
        $this->dbConnection->getDestinationChannel()->delete($this->config->getM2Prefix() . $this->table, $this->cond);
        $this->dbConnection->getDestinationChannel()->query('SET FOREIGN_KEY_CHECKS = 1;');
    }
}
