<?php

declare(strict_types=1);
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\ExportJob;

use Firebear\ImportExport\Model\ExportJob;
use Firebear\ImportExport\Model\ResourceModel\ExportJob\Collection;
use Firebear\ImportExport\Model\ResourceModel\ExportJob\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;

/**
 * Class DataProvider
 */
class DataProvider extends AbstractDataProvider
{
    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var PoolInterface
     */
    protected $pool;

    /**
     * DataProvider constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $exportCollectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param PoolInterface $pool
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $exportCollectionFactory,
        DataPersistorInterface $dataPersistor,
        PoolInterface $pool,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $exportCollectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->pool = $pool;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->addEventToResult()->getItems();
        $mergeFields = [
            ExportJob::BEHAVIOR_DATA,
            ExportJob::EXPORT_SOURCE,
            ExportJob::SOURCE_DATA
        ];
        /** @var ExportJob $job */
        foreach ($items as $job) {
            $data = $job->getData();
            foreach ($mergeFields as $name) {
                if ($data[$name]) {
                    $tempData = $data[$name];
                    unset($data[$name]);
                    $data += $tempData;
                }
            }
            if (isset($data[Processor::SOURCE_DATA_MAP])
                && !empty($data[Processor::SOURCE_DATA_MAP])
            ) {
                $sourceDataMap = $this->processSourceDataMap($data[Processor::SOURCE_DATA_MAP]);
                $data = array_merge($data, $sourceDataMap);
                unset(
                    $data[Processor::SOURCE_DATA_ENTITY],
                    $data[Processor::SOURCE_DATA_SYSTEM],
                    $data[Processor::SOURCE_DATA_EXPORT],
                    $data[Processor::SOURCE_DATA_REPLACES]
                );
            }
            if (isset($data['source_filter_map'])
                && !empty($data['source_filter_map'])
            ) {
                $sourceFilterMap = $this->processSourceFilterMap($data['source_filter_map']);
                $data = array_merge($data, $sourceFilterMap);
            }
            $this->loadedData[$job->getId()] = $data;
        }

        $data = $this->dataPersistor->get('firebear_export_job');
        if (!empty($data)) {
            $job = $this->collection->getNewEmptyItem();
            $job->setData($data);
            $this->loadedData[$job->getId()] = $job->getData();
            $this->dataPersistor->clear('firebear_export_job');
        }

        return $this->loadedData;
    }

    /**
     * @param array $sourceFilterMap
     */
    protected function processSourceFilterMap(array $sourceFilterMap)
    {
        $result['source_filter_map'] = [];
        $count = 0;
        foreach ($sourceFilterMap as $field) {
            $result['source_filter_map'][] = [
                'source_filter_entity' => $field['source_filter_entity'] ?? '',
                'source_filter_position' => $field['source_filter_position'] ?? '',
                'source_filter_field' => $field['source_filter_field'] ?? '',
                'source_filter_filter' => $field['source_filter_filter'] ?? '',
                'record_id' => ++$count
            ];
        }

        return $result;
    }

    /**
     * @param array $sourceMappings
     * @return array
     */
    protected function processSourceDataMap(array $sourceMappings)
    {
        $result[Processor::SOURCE_DATA_MAP] = [];
        foreach ($sourceMappings as $field) {
            $result[Processor::SOURCE_DATA_MAP][] = [
                Processor::SOURCE_DATA_ENTITY => $field[Processor::SOURCE_DATA_ENTITY] ?? '',
                Processor::SOURCE_DATA_SYSTEM => $field[Processor::SOURCE_DATA_SYSTEM] ?? '',
                Processor::SOURCE_DATA_EXPORT => $field[Processor::SOURCE_DATA_EXPORT] ?? '',
                Processor::SOURCE_DATA_REPLACES => $field[Processor::SOURCE_DATA_REPLACES] ?? '',
                'record_id' => $field['record_id'],
                'position' => $field['position'] ?? '',
            ];
        }

        return $result;
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function getMeta()
    {
        $meta = parent::getMeta();
        /** @var ModifierInterface $modifier */
        foreach ($this->pool->getModifiersInstances() as $modifier) {
            $meta = $modifier->modifyMeta($meta);
        }

        return $meta;
    }
}
