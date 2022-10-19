<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Source\Platform;

use Firebear\ImportExport\Model\Cache\Type\ImportProduct as ImportProductCache;
use Firebear\ImportExport\Model\Import\Product;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class Magmi
 *
 * @package Firebear\ImportExport\Model\Source\Platform
 */
class Magmi extends AbstractPlatform
{
    /**
     * @var string
     */
    protected $separator;

    /**
     * Prepare Rows
     *
     * @param $rowData
     *
     * @return mixed
     */
    public function prepareRow($rowData)
    {
        /*visibility phase*/
        if (isset($rowData['visibility'])) {
            $rowData['visibility'] = $this->getVisibilityText($rowData['visibility']);
        }

        if (isset($rowData['_store'])) {
            if ($rowData['_store'] == 'admin') {
                $rowData['_store'] = 0;
            }
            $rowData['store_view_code'] = $rowData['_store'];
        } else {
            $rowData['store_view_code'] = '';
        }

        if (isset($rowData['website'])) {
            $rowData['product_websites'] = $rowData['website'];
        } else {
            $rowData['product_websites'] = '';
        }

        if (isset($rowData['status'])) {
            // 1 - Enabled 2 - Disabled 3 - Out-of-stock
            $status = [2, 1, 3];
            if (isset($status[$rowData['status']])) {
                $rowData['status'] = $status[$rowData['status']];
            } else {
                $rowData['status'] = 0;
            }
        }

        if (isset($rowData['type']) && empty($rowData['product_type'])) {
            $rowData['product_type'] = $rowData['type'];
        }

        if (isset($rowData['tax_class_id'])) {
            $rowData['tax_class_name'] = $this->getTaxClassName($rowData['tax_class_id']);
            unset($rowData['tax_class_id']);
        }

        /*bundle phase*/
        if (isset($rowData['bundle_options']) && isset($rowData['bundle_skus'])) {
            $values = [];
            $options = explode(';', $rowData['bundle_options']);
            $skus = explode(';', $rowData['bundle_skus']);
            foreach ($options as $option) {
                list($code, $name, $type, $required, $position) = explode(':', $option);
                foreach ($skus as $row) {
                    $data = explode(':', $row);
                    $row_code = $data[0] ?? '';
                    $sku = $data[1] ?? '';
                    $qty = $data[2] ?? '';
                    $change = $data[3] ?? '';
                    $pos = $data[4] ?? '';
                    $default = $data[5] ?? '';
                    $price = $data[6] ?? '';
                    $price_type = $data[7] ?? '';
                    if ($row_code != $code) {
                        continue;
                    }

                    $value = [
                        'name=' . $name,
                        'type=' . $type,
                        'required=' . $required,
                        'sku=' . $sku,
                        'price=' . $price,
                        'default=' . $default,
                        'default_qty=' . $qty ,
                        'price_type=' . ($price_type ? 'dynamic' : 'fixed')
                    ];
                    $values[] = implode(',', $value);
                }
            }

            $rowData['bundle_values'] = implode('|', $values);

            if (isset($rowData['price_type'])) {
                $rowData['bundle_price_type'] = ($rowData['price_type'] ? 'dynamic' : 'fixed');
            } else {
                $rowData['bundle_price_type'] = 'fixed';
            }
            $rowData['price_type'] = $rowData['bundle_price_type'];

            if (isset($rowData['sku_type'])) {
                $rowData['bundle_sku_type'] = $rowData['sku_type'] ? 'dynamic' : 'fixed';
            } else {
                $rowData['bundle_sku_type'] = 'fixed';
            }
            $rowData['sku_type'] = $rowData['bundle_sku_type'];

            if (isset($rowData['weight_type'])) {
                $rowData['bundle_weight_type'] = $rowData['weight_type'] ? 'dynamic' : 'fixed';
            } else {
                $rowData['bundle_weight_type'] = 'fixed';
            }
            $rowData['weight_type'] = $rowData['bundle_weight_type'];

            if (isset($rowData['price_view'])) {
                $rowData['bundle_price_view'] = $rowData['price_view'] ? 'Price Range' : 'As Low as';
            } else {
                $rowData['bundle_price_view'] = 'Price Range';
            }
            $rowData['price_view'] = $rowData['bundle_price_view'];

            if (isset($rowData['options_container'])) {
                unset($rowData['options_container']);
            }

            if (isset($rowData['shipment_type'])) {
                $rowData['shipment_type'] = $rowData['shipment_type'] ? 'Together' : 'Separately';
            } else {
                $rowData['shipment_type'] = 'Together';
            }
        }

        /*Config Product Phase*/
        if (!empty($rowData['simples_skus'])
            && !empty($rowData['configurable_attributes'])
            && !empty($rowData['super_attribute_pricing'])
        ) {
            $values = [];
            $superAttributes = explode(';', $rowData['super_attribute_pricing']);
            $options = explode(',', $rowData['configurable_attributes']);
            $skus = explode(',', $rowData['simples_skus']);
            $code = '';
            foreach ($superAttributes as $key => $superAttribute) {
                $as = explode(':', $superAttribute);
                if ($code === '') {
                    $code = $as[0] ?? '';
                }
                $v = $as[2] ?? $as[0];
                $value = [
                    'sku=' . $skus[$key],
                    $code.'='.$v
                ];
                $values[] = implode(',', $value);
            }
            $rowData['configurable_variations'] = implode('|', $values);
        }
        return $rowData;
    }

    /**
     * @param $rowData
     * @return mixed
     */
    public function prepareColumns($rowData)
    {
        if (in_array('tax_class_id', $rowData)) {
            $key = array_search('tax_class_id', $rowData);
            $rowData[$key] = 'tax_class_name';
        }
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
        $_currentRowSkus = [];

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
                $model->addLogWriteln(__('Saving Validated Bunches'), $model->getOutput(), 'info');
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
                $_currentRowSkus[] = mb_strtolower($rowData[$model::COL_SKU]);
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
        if (!empty($parameters['disable_products']) && !empty($_currentRowSkus)) {
            $model->disableProductsNotInList($_currentRowSkus);
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

    /**
     * Get tax class name
     *
     * @param $taxId
     *
     * @return mixed|string
     */
    public function getTaxClassName($taxId)
    {
        if (!is_numeric($taxId)) {
            return '';
        }
        $taxInfo = $this->taxFactory->create()->load($taxId);

        if ($taxInfo->getClassName()) {
            return $taxInfo->getClassName();
        }

        return '';
    }
}
