<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration\Field\Job;

use Firebear\ImportExport\Model\Migration\Config;
use Firebear\ImportExport\Model\Migration\DbConnection;
use Firebear\ImportExport\Model\Migration\Field\JobInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * @inheritdoc
 */
class MapFromTable implements JobInterface
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
    protected $keyField;

    /**
     * @var string
     */
    protected $dataField;

    /**
     * @var array
     */
    protected $mapping;

    /**
     * @param DbConnection $dbConnection
     * @param Config $config
     * @param string $table
     * @param string $keyField
     * @param string $dataField
     */
    public function __construct(
        DbConnection $dbConnection,
        Config $config,
        string $table,
        string $keyField,
        string $dataField
    ) {
        $this->dbConnection = $dbConnection;
        $this->config = $config;
        $this->table = $table;
        $this->keyField = $keyField;
        $this->dataField = $dataField;
        $this->mapping = $this->fetchMapping();
    }

    /**
     * @return array
     */
    protected function fetchMapping()
    {
        $select = $this->dbConnection->getSourceChannel()
            ->select()
            ->from($this->config->getM1Prefix() . $this->table, [$this->keyField, $this->dataField]);

        $mapping = $this->dbConnection->getSourceChannel()->fetchPairs($select);

        return $mapping;
    }

    /**
     * @inheritdoc
     */
    public function job(
        $sourceField,
        $sourceValue,
        $destinationFiled,
        $destinationValue,
        $sourceDataRow
    ) {
        if (!isset($sourceDataRow[$this->keyField])) {
            throw new LocalizedException(__("Key field %1 not found in source data.", $this->keyField));
        }

        $keyValue = $sourceDataRow[$this->keyField];

        if (!isset($this->mapping[$keyValue])) {
            throw new LocalizedException(__("Mapping not found for key value %1", $keyValue));
        }

        return $this->mapping[$keyValue];
    }
}
