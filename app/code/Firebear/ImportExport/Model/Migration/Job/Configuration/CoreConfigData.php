<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration\Job\Configuration;

use Firebear\ImportExport\Model\Migration\Config;
use Firebear\ImportExport\Model\Migration\DbConnection;
use Firebear\ImportExport\Model\Migration\Field\Job\MapStoreId;
use Firebear\ImportExport\Model\Migration\Field\Job\MapWebsiteId;
use Firebear\ImportExport\Model\Migration\Field\JobInterface as FieldJobInterface;
use Firebear\ImportExport\Model\Migration\FilterJobs\StoreId;
use Firebear\ImportExport\Model\Migration\FilterJobs\WebsiteId;
use Firebear\ImportExport\Model\Migration\JobInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Firebear\ImportExport\Model\Migration\Job\Configuration
 */
class CoreConfigData implements JobInterface
{
    const CONFIG_TABLE = 'core_config_data';

    /**
     * @var DbConnection
     */
    private $dbConnection;

    /**
     * @var StoreId
     */
    private $storeIdFilterJobs;

    /**
     * @var MapStoreId
     */
    private $storeIdMapper;

    /**
     * @var WebsiteId
     */
    private $websiteIdFilterJobs;

    /**
     * @var MapWebsiteId
     */
    private $websiteIdMapper;

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
     * @param StoreId $storeIdFilterJobs
     * @param MapStoreId $storeIdMapper
     * @param WebsiteId $websiteIdFilterJobs
     * @param MapWebsiteId $websiteIdMapper
     * @param Config $config
     * @param array $mapping
     * @param array $valueProcessors
     */
    public function __construct(
        DbConnection $dbConnection,
        StoreId $storeIdFilterJobs,
        MapStoreId $storeIdMapper,
        WebsiteId $websiteIdFilterJobs,
        MapWebsiteId $websiteIdMapper,
        Config $config,
        array $mapping = [],
        array $valueProcessors = []
    ) {
        $this->dbConnection = $dbConnection;
        $this->storeIdFilterJobs = $storeIdFilterJobs;
        $this->storeIdMapper = $storeIdMapper;
        $this->websiteIdFilterJobs = $websiteIdFilterJobs;
        $this->websiteIdMapper = $websiteIdMapper;
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
    protected function getDestinationPathArray($sourcePath)
    {
        $values = $this->getMapping();

        $destinationPath = $values[$sourcePath];

        if (!is_array($destinationPath)) {
            $destinationPath = [$destinationPath];
        }

        return $destinationPath;
    }

    /**
     * @inheritDoc
     */
    public function job($output, $additionalOptions = null)
    {
        $source = $this->getDbConnection()->getSourceChannel();
        $destination = $this->getDbConnection()->getDestinationChannel();

        $sourceSelect = $source->select()
            ->from($this->config->getM1Prefix() . self::CONFIG_TABLE, [
                'scope' => 'scope',
                'scope_id' => 'scope_id',
                'path' => 'path',
                'value' => 'value',
            ]);

        $storeScopeIdExpr = $source->quoteInto('scope_id IN (?)', $this->storeIdFilterJobs->getStoreIds());
        $websiteScopeIdExpr = $source->quoteInto('scope_id IN (?)', $this->websiteIdFilterJobs->getWebsiteIds());

        $expression = new \Zend_Db_Expr(
            "scope = 'default' OR (scope = 'stores' AND 
            {$storeScopeIdExpr}) OR (scope = 'websites' AND 
            {$websiteScopeIdExpr})"
        );
        $sourceSelect->where($expression);

        $sourceData = $source->query($sourceSelect);
        $destinationData = [];

        while ($sourceDataRow = $sourceData->fetch()) {
            $sourcePath = $sourceDataRow['path'];

            if (in_array($sourcePath, $this->getSourcePaths())) {
                $destinationPathArray = $this->getDestinationPathArray($sourcePath);

                foreach ($destinationPathArray as $destinationPath) {
                    $destinationScope = $sourceDataRow['scope'];

                    if ($destinationScope == 'stores') {
                        $destinationScopeId = $this->storeIdMapper->job(
                            'scope_id',
                            $sourceDataRow['scope_id'],
                            'scope_id',
                            $sourceDataRow['scope_id'],
                            $sourceDataRow
                        );
                    } elseif ($destinationScope == 'websites') {
                        $destinationScopeId = $this->websiteIdMapper->job(
                            'scope_id',
                            $sourceDataRow['scope_id'],
                            'scope_id',
                            $sourceDataRow['scope_id'],
                            $sourceDataRow
                        );
                    } else {
                        $destinationScopeId = 0;
                    }

                    $destinationValue = $sourceDataRow['value'];

                    $valueProcessor = $this->getValueProcessor($destinationPath);

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

                    $destinationDataRow = [
                        'config_id' => null,
                        'scope' => $destinationScope,
                        'scope_id' => $destinationScopeId,
                        'path' => $destinationPath,
                        'value' => $destinationValue,
                    ];

                    $existingSelect = $destination->select()
                        ->from($this->config->getM2Prefix() . self::CONFIG_TABLE, ['config_id'])
                        ->where('scope = ?', $destinationScope)
                        ->where('scope_id = ?', $destinationScopeId)
                        ->where('path = ?', $destinationPath);

                    $existingId = $destination->fetchOne($existingSelect);

                    if ($existingId) {
                        $destinationDataRow['config_id'] = $existingId;
                    }

                    $destinationData[] = $destinationDataRow;
                }
            }
        }

        foreach (array_chunk($destinationData, 1000) as $destinationBatch) {
            $this->getDbConnection()->getDestinationChannel()->insertOnDuplicate(
                $this->config->getM2Prefix() . self::CONFIG_TABLE,
                $destinationBatch
            );
        }
    }
}
