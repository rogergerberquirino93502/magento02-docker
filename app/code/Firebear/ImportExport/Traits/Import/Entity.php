<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Traits\Import;

use Firebear\ImportExport\Helper\Data as DataHelper;
use Firebear\ImportExport\Model\ResourceModel\Import\Data as ResourceModelData;
use Firebear\ImportExport\Traits\General as GeneralTrait;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\ImportExport\Helper\Data as ImportExportData;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\ResourceModel\Helper as ResourceHelper;

trait Entity
{
    use GeneralTrait;

    /**
     * @var JsonHelper
     */
    protected $jsonHelper;

    /**
     * @var ImportExportData
     */
    protected $_importExportData;

    /**
     * @var ResourceModelData
     */
    protected $_dataSourceModel;

    /**
     * @var ResourceHelper
     */
    protected $_resourceHelper;

    /**
     * @var DataHelper
     */
    protected $_helper;

    /**
     * @param $file
     * @param $offset
     * @param $jobId
     * @return mixed
     */
    public function importDataPart($file, $offset, $jobId)
    {
        $this->setDataSourceData($file, $jobId, $offset);

        return $this->importData();
    }

    /**
     * @param $file
     * @param $jobId
     * @param $offset
     */
    public function setDataSourceData($file, $jobId, $offset)
    {
        if (!preg_match('/^[0-9-]+$/', $file)) {
            return;
        }

        $this->_dataSourceModel->setFile($file);
        $this->_dataSourceModel->setJobId((int)$jobId);
        $this->_dataSourceModel->setOffset((int)$offset);
    }

    /**
     * @param int $saveBunches
     *
     * @return mixed
     */
    public function validateData($saveBunches = 1)
    {
        if (isset($this->_parameters['output'])) {
            $this->output = $this->_parameters['output'];
        }

        if (!$this->_dataValidated) {
            $this->getErrorAggregator()->clear();
            // do all permanent columns exist?
            $absentColumns = array_diff($this->_permanentAttributes, $this->getSource()->getColNames());
            $this->addErrors(self::ERROR_CODE_COLUMN_NOT_FOUND, $absentColumns);

            $platform = null;
            if (!empty($this->_parameters['platforms'])) {
                $platform = $this->_helper->getPlatformModel(
                    $this->_parameters['platforms'],
                    $this->getEntityTypeCode()
                );
            }

            if (Import::BEHAVIOR_DELETE != $this->getBehavior()) {
                // check attribute columns names validity
                $columnNumber = 0;
                $emptyHeaderColumns = [];
                $invalidColumns = [];
                $invalidAttributes = [];
                $pattern = ($platform && method_exists($platform, 'getPattern'))
                    ? $platform->getPattern()
                    : '/^[a-z][a-z0-9_\:]*$/';
                foreach ($this->getSource()->getColNames() as $columnName) {
                    $this->addLogWriteln(__('Checked column %1', $columnName), $this->output);
                    $isNewAttribute = true;
                    $columnNumber++;
                    if (!$this->isAttributeParticular($columnName)) {
                        if (trim($columnName) == '') {
                            $emptyHeaderColumns[] = $columnNumber;
                        } elseif (!preg_match($pattern, $columnName)) {
                            $invalidColumns[] = $columnName;
                        } elseif ($this->needColumnCheck && !in_array($columnName, $this->getValidColumnNames())) {
                            $invalidAttributes[] = $columnName;
                        }
                    }
                }
                //@todo more improvements required here in case of customPlatform interface more dynamic
                $this->addErrors(self::ERROR_CODE_INVALID_ATTRIBUTE, $invalidAttributes);
                $this->addErrors(self::ERROR_CODE_COLUMN_EMPTY_HEADER, $emptyHeaderColumns);
                if (!$platform
                    && isset($this->_parameters['platforms'])
                    && $this->_parameters['platforms'] !== 'wooAttributes'
                ) {
                    $this->addErrors(self::ERROR_CODE_COLUMN_NAME_INVALID, $invalidColumns);
                }
                $this->addLogWriteln(__('Finish checking columns'), $this->output);
                $this->addLogWriteln(
                    __('Errors count: %1', $this->getErrorAggregator()->getErrorsCount()),
                    $this->output
                );
            }

            if (!$this->getErrorAggregator()->getErrorsCount()) {
                if ($saveBunches) {
                    $maxDataSize = isset($this->_maxDataSize) ?: $this->_resourceHelper->getMaxDataSize();
                    $maxBunchSize = isset($this->_bunchSize) ?: $this->_importExportData->getBunchSize();
                    $this->addLogWriteln(__('Start saving bunches'), $this->output);
                    if ($platform && method_exists($platform, 'saveValidatedBunches')) {
                        $platform->saveValidatedBunches(
                            $this->_source,
                            $maxDataSize,
                            $maxBunchSize,
                            $this->_dataSourceModel,
                            $this->_parameters,
                            $this->getEntityTypeCode(),
                            $this->getBehavior(),
                            $this->_processedRowsCount,
                            '|',
                            $this
                        );
                    } else {
                        $this->_saveValidatedBunches();
                    }
                    $this->addLogWriteln(__('Finish saving bunches'), $this->output);
                }
                $this->_dataValidated = true;
            }
        }

        return $this->getErrorAggregator();
    }

    /**
     * @return JsonHelper
     */
    public function getJsonHelper()
    {
        return $this->jsonHelper;
    }

    /**
     * @param $errorAggregator
     *
     * @return mixed
     */
    public function setErrorAggregator($errorAggregator)
    {
        return $this->errorAggregator = $errorAggregator;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function joinIdenticalyData($data)
    {
        $reverts = [];
        foreach ($this->_parameters['map'] as $item) {
            if ($item['import']) {
                $reverts[$item['import']][] = $item['system'];
            }
        }
        if (!empty($this->_parameters['identicaly'])) {
            foreach ($this->_parameters['identicaly'] as $elem) {
                if (!empty($data[$reverts[$elem['import']][0]])) {
                    $data[$elem['system']] = $data[$reverts[$elem['import']][0]];
                }
            }
        }
        return $data;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->_parameters;
    }
}
