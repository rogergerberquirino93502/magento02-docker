<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Setup\Db\Adapter\Pdo;

use Magento\Framework\DB\Ddl\Table;

/**
 * Class Mysql
 *
 * @package Firebear\ImportExport\Setup\Db\Adapter\Pdo
 */
class Mysql extends \Magento\Framework\DB\Adapter\Pdo\Mysql
{
    /**
     * MySQL column - Table DDL type pairs
     *
     * @var array
     */
    protected $_ddlColumnTypes      = [
        Table::TYPE_BOOLEAN       => 'bool',
        Table::TYPE_SMALLINT      => 'smallint',
        Table::TYPE_INTEGER       => 'int',
        Table::TYPE_BIGINT        => 'bigint',
        Table::TYPE_FLOAT         => 'float',
        Table::TYPE_DECIMAL       => 'decimal',
        Table::TYPE_NUMERIC       => 'decimal',
        Table::TYPE_DATE          => 'date',
        Table::TYPE_TIMESTAMP     => 'timestamp',
        Table::TYPE_DATETIME      => 'datetime',
        Table::TYPE_TEXT          => 'text',
        Table::TYPE_BLOB          => 'blob',
        Table::TYPE_VARBINARY     => 'blob',
        'longblob'                => 'longblob'
    ];
}
