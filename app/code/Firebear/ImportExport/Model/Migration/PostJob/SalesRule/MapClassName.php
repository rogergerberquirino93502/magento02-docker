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
class MapClassName implements PostJobInterface
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
            ->from($this->table, ['rule_id', 'conditions_serialized', 'actions_serialized']);

        $ets = $this->dbConnection->getDestinationChannel()->query($select)->fetchAll();
        $this->dbConnection->getDestinationChannel()->query('SET FOREIGN_KEY_CHECKS = 0;');

        foreach ($ets as $item) {
            $conditions_serialized = stripslashes($item['conditions_serialized']);
            $actions_serialized = stripslashes($item['actions_serialized']);
            //Find block
            if (strpos($conditions_serialized, 'rule_condition_combine') !== false) {
                $pattern = '/salesrule\/rule_condition_combine/i';
                $replacement = addslashes(addslashes(\Magento\SalesRule\Model\Rule\Condition\Combine::class));
                $conditions_serialized = preg_replace($pattern, $replacement, $conditions_serialized);
            }

            if (strpos($conditions_serialized, 'rule_condition_address') !== false) {
                $pattern = '/salesrule\/rule_condition_address/i';
                $replacement = addslashes(addslashes(\Magento\SalesRule\Model\Rule\Condition\Address::class));
                $conditions_serialized = preg_replace($pattern, $replacement, $conditions_serialized);
            }

            if (strpos($conditions_serialized, 'rule_condition_product') !== false) {
                $pattern = '/salesrule\/rule_condition_product/i';
                $replacement = addslashes(addslashes(\Magento\SalesRule\Model\Rule\Condition\Product::class));
                $conditions_serialized = preg_replace($pattern, $replacement, $conditions_serialized);
            }

            if (strpos($conditions_serialized, 'rule_condition_product_combine') !== false) {
                $pattern = '/salesrule\/rule_condition_product_combine/i';
                $replacement = addslashes(addslashes(\Magento\SalesRule\Model\Rule\Condition\Product\Combine::class));
                $conditions_serialized = preg_replace($pattern, $replacement, $conditions_serialized);
            }

            if (strpos($conditions_serialized, 'rule_condition_product_found') !== false) {
                $pattern = '/salesrule\/rule_condition_product_found/i';
                $replacement =  addslashes(addslashes(\Magento\SalesRule\Model\Rule\Condition\Product\Found::class));
                $conditions_serialized = preg_replace($pattern, $replacement, $conditions_serialized);
            }

            if (strpos($conditions_serialized, 'rule_condition_product_subselect') !== false) {
                $pattern = '/salesrule\/rule_condition_product_subselect/i';
                $replacement =  addslashes(addslashes(
                    \Magento\SalesRule\Model\Rule\Condition\Product\Subselect::class
                ));
                $conditions_serialized = preg_replace($pattern, $replacement, $conditions_serialized);
            }

            if (strpos($actions_serialized, 'rule_condition_combine') !== false) {
                $pattern = "/salesrule\/rule_condition_combine/i";
                $replacement =  addslashes(addslashes(\Magento\SalesRule\Model\Rule\Condition\Combine::class));
                $actions_serialized = preg_replace($pattern, $replacement, $actions_serialized);
            }

            if (strpos($actions_serialized, 'rule_condition_product') !== false) {
                $pattern = '/salesrule\/rule_condition_address/i';
                $replacement = addslashes(addslashes(\Magento\SalesRule\Model\Rule\Condition\Address::class));
                $actions_serialized = preg_replace($pattern, $replacement, $actions_serialized);
            }

            if (strpos($actions_serialized, 'rule_condition_address') !== false) {
                $pattern = '/salesrule\/rule_condition_product/i';
                $replacement = addslashes(addslashes(\Magento\SalesRule\Model\Rule\Condition\Product::class));
                $actions_serialized = preg_replace($pattern, $replacement, $actions_serialized);
            }

            if (strpos($actions_serialized, 'rule_condition_product_combine') !== false) {
                $pattern = '/salesrule\/rule_condition_product_combine/i';
                $replacement =  addslashes(addslashes(\Magento\SalesRule\Model\Rule\Condition\Product\Combine::class));
                $actions_serialized = preg_replace($pattern, $replacement, $actions_serialized);
            }

            if (strpos($actions_serialized, 'rule_condition_product_found') !== false) {
                $pattern = '/salesrule\/rule_condition_product_found/i';
                $replacement =  addslashes(addslashes(\Magento\SalesRule\Model\Rule\Condition\Product\Found::class));
                $actions_serialized = preg_replace($pattern, $replacement, $actions_serialized);
            }

            if (strpos($actions_serialized, 'rule_condition_product_subselect') !== false) {
                $pattern = '/salesrule\/rule_condition_product_subselect/i';
                $replacement =  addslashes(addslashes(
                    \Magento\SalesRule\Model\Rule\Condition\Product\Subselect::class
                ));
                $actions_serialized = preg_replace($pattern, $replacement, $actions_serialized);
            }

            //Update to database;
            $conditions_serialized = $this->dbConnection->getDestinationChannel()->quote($conditions_serialized);
            $actions_serialized = $this->dbConnection->getDestinationChannel()->quote($actions_serialized);
            $query = 'UPDATE ' . $this->table . ' SET
                `conditions_serialized` = ' . $conditions_serialized . ',
                `actions_serialized` = ' . $actions_serialized . '
             WHERE `rule_id` = ' . $item['rule_id'] . ';';
            $this->dbConnection->getDestinationChannel()->query($query);
        }
        $this->dbConnection->getDestinationChannel()->query('SET FOREIGN_KEY_CHECKS = 1;');
    }
}
