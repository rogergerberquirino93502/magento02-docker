<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration\Job\Configuration;

use Firebear\ImportExport\Model\Migration\Config;
use Firebear\ImportExport\Model\Migration\DbConnection;
use Firebear\ImportExport\Model\Migration\Field\JobInterface as FieldJobInterface;
use Firebear\ImportExport\Model\Migration\JobInterface;
use Magento\Framework\FlagManager;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Firebear\ImportExport\Model\Migration\Job\Configuration
 */
class CoreConfigDataToFlag implements JobInterface
{
    const CONFIG_TABLE = 'core_config_data';

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
     * @var array
     */
    private $valueProcessors = [];

    /**
     * @var Config
     */
    private $config;

    /**
     * @param DbConnection $dbConnection
     * @param FlagManager $flagManager
     * @param Config $config
     * @param array $mapping
     * @param array $valueProcessors
     */
    public function __construct(
        DbConnection $dbConnection,
        FlagManager $flagManager,
        Config $config,
        array $mapping = [],
        array $valueProcessors = []
    ) {
        $this->dbConnection = $dbConnection;
        $this->flagManager = $flagManager;
        $this->config = $config;
        $this->mapping = $mapping;
        $this->valueProcessors = $valueProcessors;
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
     * @param string $destinationPath
     *
     * @return FieldJobInterface|string|null
     */
    protected function getValueProcessor($destinationPath)
    {
        if (isset($this->valueProcessors[$destinationPath])) {
            return $this->valueProcessors[$destinationPath];
        }

        return null;
    }

    /**
     * @return array
     */
    protected function getSourcePaths()
    {
        return array_keys($this->getMapping());
    }

    /**
     * @param string $sourcePath
     *
     * @return array
     */
    protected function getDestinationFlagNames($sourcePath)
    {
        $values = $this->getMapping();

        $destinationPath = $values[$sourcePath];

        if (!is_array($destinationPath)) {
            $destinationPath = [$destinationPath];
        }

        return $destinationPath;
    }

    /**
     * @inheritdoc
     */
    public function job($output, $additionalOptions = null)
    {
        $source = $this->getDbConnection()->getSourceChannel();

        $sourceSelect = $source->select()
            ->from($this->config->getM1Prefix() . self::CONFIG_TABLE, [
                'scope' => 'scope',
                'scope_id' => 'scope_id',
                'path' => 'path',
                'value' => 'value',
            ])
            ->where('scope_id = ?', 0)
            ->where('scope = ?', 'default');

        $sourceData = $source->query($sourceSelect);

        while ($sourceDataRow = $sourceData->fetch()) {
            $sourcePath = $sourceDataRow['path'];

            if (in_array($sourcePath, $this->getSourcePaths())) {
                $destinationFlagNames = $this->getDestinationFlagNames($sourcePath);

                foreach ($destinationFlagNames as $destinationFlagName) {
                    $destinationValue = $sourceDataRow['value'];
                    $valueProcessor = $this->getValueProcessor($destinationFlagName);

                    if ($valueProcessor instanceof FieldJobInterface) {
                        $destinationValue = $valueProcessor->job(
                            'value',
                            $sourceDataRow['value'],
                            'value',
                            $destinationValue,
                            $sourceDataRow
                        );
                    } elseif ($valueProcessor !== null) {
                        $destinationValue = $valueProcessor;
                    }

                    $this->flagManager->saveFlag($destinationFlagName, $destinationValue);
                }
            }
        }
    }
}
