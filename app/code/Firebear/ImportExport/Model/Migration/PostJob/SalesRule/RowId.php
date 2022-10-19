<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration\PostJob\SalesRule;

use Firebear\ImportExport\Model\Migration\DbConnection;
use Firebear\ImportExport\Model\Migration\PostJobInterface;

/**
 * @inheritdoc
 */
class RowId implements PostJobInterface
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
     * @param DbConnection $dbConnection
     * @param string $table
     */
    public function __construct(
        DbConnection $dbConnection,
        string $table
    ) {
        $this->dbConnection = $dbConnection;
        $this->table = $table;
    }

    /**
     * @inheritdoc
     */
    public function job()
    {
        $select = $this->dbConnection->getDestinationChannel()
            ->select()
            ->from($this->table, ['rule_id']);
        $ets = $this->dbConnection->getDestinationChannel()->query($select)->fetchAll();
        $listRuleId = [];
        $this->dbConnection->getDestinationChannel()->query('SET FOREIGN_KEY_CHECKS = 0;');
        foreach ($ets as $item) {
            $row_id = $item['rule_id'];
            if (!in_array($row_id, $listRuleId)) {
                $select = $this->dbConnection->getDestinationChannel()
                    ->select()
                    ->from($this->table, ['rule_id']);
                $data = $this->dbConnection->getDestinationChannel()->fetchRow($select);
                if (is_array($data)) {
                    $query = "UPDATE " . $this->table . " SET `rule_id` = "
                        . $data['rule_id'] . " WHERE `rule_id` = "
                        . $row_id . ";";
                    $this->dbConnection->getDestinationChannel()->query($query);
                }
                $listRuleId[] = $row_id;
            }
        }
        $this->dbConnection->getDestinationChannel()->query('SET FOREIGN_KEY_CHECKS = 1;');
    }
}
