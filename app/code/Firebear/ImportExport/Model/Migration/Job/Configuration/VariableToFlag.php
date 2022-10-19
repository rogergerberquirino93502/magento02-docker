<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration\Job\Configuration;

use Firebear\ImportExport\Model\Migration\Config;
use Firebear\ImportExport\Model\Migration\DbConnection;
use Firebear\ImportExport\Model\Migration\JobInterface;
use Magento\Framework\FlagManager;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Firebear\ImportExport\Model\Migration\Job\Configuration
 */
class VariableToFlag implements JobInterface
{
    const TABLE_VARIABLE = 'core_variable';
    const TABLE_VARIABLE_VALUE = 'core_variable_value';

    /**
     * @var DbConnection
     */
    private $dbConnection;

    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @var array
     */
    private $mapping = [];

    /**
     * @var Config
     */
    private $config;

    /**
     * @param DbConnection $dbConnection
     * @param FlagManager $flagManager
     * @param Config $config
     * @param array $mapping
     */
    public function __construct(
        DbConnection $dbConnection,
        FlagManager $flagManager,
        Config $config,
        array $mapping = []
    ) {
        $this->dbConnection = $dbConnection;
        $this->flagManager = $flagManager;
        $this->config = $config;
        $this->mapping = $mapping;
    }

    /**
     * @return DbConnection
     */
    protected function getDbConnection()
    {
        return $this->dbConnection;
    }

    /**
     * @return array
     */
    protected function getMapping()
    {
        return $this->mapping;
    }

    /**
     * @inheritdoc
     */
    public function job($output, $additionalOptions = null)
    {
        $source = $this->getDbConnection()->getSourceChannel();

        $sourceSelect = $source->select()
            ->from(
                ['variable' => $this->config->getM1Prefix() . self::TABLE_VARIABLE],
                ['name' => 'variable.code']
            )
            ->join(
                ['value' => $this->config->getM1Prefix() . self::TABLE_VARIABLE_VALUE],
                'variable.variable_id = value.variable_id',
                ['value' => 'value.plain_value']
            );

        $sourceData = $source->query($sourceSelect);

        while ($sourceDataRow = $sourceData->fetch()) {
            $variableName = $sourceDataRow['name'];
            $variableValue = $sourceDataRow['value'];

            $mapping = $this->getMapping();

            if (array_key_exists($variableName, $mapping)) {
                $flagName = $mapping[$variableName];

                $this->flagManager->saveFlag($flagName, $variableValue);
            }
        }
    }
}
