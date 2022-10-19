<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Platform;

use Firebear\ImportExport\Model\Cache\Type\ImportProduct as ImportProductCache;
use Magento\Catalog\Model\Product\Visibility;
use Firebear\ImportExport\Model\Import\Product;
use Magento\Backend\Model\Session;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory;
use Magento\Eav\Model\Entity\Context;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\File\ReadFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Model\ClassModelFactory;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class Shopify
 *
 * @package Firebear\ImportExport\Model\Source\Platform
 */
class Shopify extends AbstractPlatform
{
    /**
     * @var string
     */
    protected $separator;

    /**
     * @param $rowData
     * @return mixed
     */
    public function prepareRow($rowData)
    {
        $config = '';
        foreach ($rowData as $key => $item) {
            if (strpos($key, 'Option') !== false && strrpos($key, 'Name') !== false) {
                if (!empty($item)) {
                    $name = str_replace("Name", "Value", $key);
                    $rowData[strtolower($item)] = str_replace("/", "|", $rowData[$name]);
                    $config .= "," . strtolower($item) . "=" . $rowData[strtolower($item)];
                }
            }
        }
        if (!empty($config)) {
            $rowData['config'] = $config;
        }

        return $rowData;
    }

    /**
     * @param $rowData
     * @return mixed
     */
    public function prepareColumns($rowData)
    {
        return $rowData;
    }

    /**
     * @param $data
     * @param $maps
     * @return mixed
     */
    public function afterColumns($data, $maps)
    {
        if (empty($maps)) {
            return $data;
        }
        $systems = [];
        foreach ($maps as $field) {
            $systems[] = $field['system'];
        }
        foreach ($data as $key => $item) {
            if (!in_array($item, $systems)) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    public function deleteColumns($array)
    {
        return $array;
    }

    /**
     * @param $source
     * @param $maxDataSize
     * @param $bunchSize
     * @param $dataSourceModel
     * @param $parameters
     * @param $entityTypeCode
     * @param $behavior
     * @param $processedRowsCount
     * @param $separator
     * @param Product $model
     *
     * @return $this
     */
    public function saveValidatedBunches(
        $source,
        $maxDataSize,
        $bunchSize,
        $dataSourceModel,
        $parameters,
        $entityTypeCode,
        $behavior,
        $processedRowsCount,
        $separator,
        Product $model
    ) {
        $currentDataSize = 0;
        $bunchRows = [];
        $prevData = [];
        $startNewBunch = false;
        $nextRowBackup = [];
        $repeatStore = 0;
        $source->rewind();
        $dataSourceModel->cleanBunches();
        $configurables = [];
        $simplesCount = 0;
        $file = null;
        $jobId = null;

        if (isset($parameters['file'])) {
            $file = $parameters['file'];
        }
        if (isset($parameters['job_id'])) {
            $jobId = $parameters['job_id'];
        }
        $repeats = 0;
        $end = 0;
        while ($source->valid() || $bunchRows) {
            if ($startNewBunch || !$source->valid()) {
                $dataSourceModel->saveBunches(
                    $entityTypeCode,
                    $behavior,
                    $jobId,
                    $file,
                    $bunchRows
                );
                $bunchRows = $nextRowBackup;
                $currentDataSize = strlen($model->getJsonHelper()->jsonEncode($bunchRows));
                $startNewBunch = false;
                $nextRowBackup = [];
            }
            if ($source->valid()) {
                try {
                    $rowData = $source->current();
                    $isCached = $parameters['cache_products'] ?? false;
                    if ($isCached) {
                        $isRowDataInCache = $this->isRowDataInCache($model, $rowData);
                        if ($isRowDataInCache) {
                            $source->next();
                            continue;
                        }
                    }
                } catch (\InvalidArgumentException $e) {
                    $model->addRowError($e->getMessage(), $processedRowsCount);
                    $processedRowsCount++;
                    $source->next();
                    continue;
                }
                if (empty($rowData['sku']) && !$end) {
                    $prevData = $this->mergeData($rowData, $prevData, $separator);
                    $source->next();
                    $repeats++;
                    if ($source->valid()) {
                        continue;
                    } else {
                        $end = 1;
                    }
                }

                $rowData = $model->customFieldsMapping($rowData);
                if (empty($rowData['name'])) {
                    $repeatStore = 1;
                }
                if (!empty($prevData) && $repeats > 0) {
                    $this->separator = $separator;
                    $prevData = $this->prepareRow($prevData);
                    if ($simplesCount > 0 && isset($prevData['config'])) {
                        $configurables['items'][] = 'sku=' . $prevData['sku'] . $prevData['config'];
                    }
                    $processedRowsCount++;
                    $rowSize = strlen($model->getJsonHelper()->jsonEncode($prevData));
                    $isBunchSizeExceeded = $bunchSize > 0 && count($bunchRows) >= $bunchSize;
                    if ($currentDataSize + $rowSize >= $maxDataSize || $isBunchSizeExceeded) {
                        $startNewBunch = true;
                        $nextRowBackup = [$processedRowsCount => $prevData];
                    } else {
                        $bunchRows[$processedRowsCount] = $prevData;
                        $currentDataSize += $rowSize;
                    }
                }
                if ($repeatStore) {
                    $simplesCount++;
                    $prevData = array_merge($prevData, $this->deleteEmpty($rowData));
                    if ($simplesCount == 1) {
                        $configurables = ['data' => $prevData, 'items' => []];
                    }
                    $repeatStore = 0;
                } else {
                    if (!empty($configurables)) {
                        $confData = $configurables['data'];
                        $confData['sku'] .= '-Conf';
                        $confData['product_type'] = 'configurable';
                        $confData['configurable_variations'] = implode("|", $configurables['items']);
                        $processedRowsCount++;
                        $rowSize = strlen($model->getJsonHelper()->jsonEncode($confData));
                        $isBunchSizeExceeded = $bunchSize > 0 && count($bunchRows) >= $bunchSize;
                        if ($currentDataSize + $rowSize >= $maxDataSize || $isBunchSizeExceeded) {
                            $startNewBunch = true;
                            $nextRowBackup = [$processedRowsCount => $prevData];
                        } else {
                            $bunchRows[$processedRowsCount] = $confData;
                            $currentDataSize += $rowSize;
                        }
                    }
                    $simplesCount = 0;
                    $prevData = $rowData;
                }
                if (!$end) {
                    $repeats = 1;
                    $key = $source->key();
                }

                $source->next();
                if (!$source->valid() && $end == 0) {
                    $source->rewind();
                    $source->seek($key);
                    $end = 1;
                }
            }
        }
        $this->setProcessedRowsCount($processedRowsCount);
        if (empty($processedRowsCount)) {
            $errorMessage = __('This file is empty. Please try another one.');
            $model->addLogWriteln($errorMessage, null, 'warning');
        }
        $this->saveRowDataInBuffCache($model);
        return $this;
    }

    protected function deleteEmpty($array)
    {
        $newElement = [];
        foreach ($array as $key => $element) {
            if (strlen($element)) {
                $newElement[$key] = $element;
            }
        }

        return $newElement;
    }

    protected function mergeData($rowData, $prevData, $separator)
    {

        $data = $this->deleteEmpty($rowData);
        foreach ($data as $key => $value) {
            if (isset($prevData[$key])) {
                if ($prevData[$key] != $rowData[$key]) {
                    $prevData[$key] .= $separator . $value;
                }
            }
        }

        return $prevData;
    }
}
