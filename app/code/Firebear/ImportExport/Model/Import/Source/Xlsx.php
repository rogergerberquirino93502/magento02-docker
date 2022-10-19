<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Source;

use OpenSpout\Reader\ReaderInterface;
use OpenSpout\Reader\Common\Creator\ReaderFactory;
use Firebear\ImportExport\Model\Source\Platform\PlatformInterface;
use Firebear\ImportExport\Traits\Import\Map as ImportMap;
use InvalidArgumentException;
use Magento\Framework\Filesystem\Directory\Read as Directory;
use Magento\ImportExport\Model\Import\AbstractEntity;
use Magento\ImportExport\Model\Import\AbstractSource;
use Exception;

/**
 * Xlsx Import Adapter
 */
class Xlsx extends AbstractSource
{
    use ImportMap;

    /**
     * Row Iterator
     *
     * @var RowIterator
     */
    protected $rowIterator;

    /**
     * Spreadsheet Reader
     *
     * @var ReaderInterface
     */
    protected $reader;

    /**
     * Column Map
     *
     * @var array
     */
    protected $maps = [];

    /**
     * Office Document File Extension
     *
     * @var array
     */
    protected $extension = 'xlsx';

    /**
     * Platform
     *
     * @var PlatformInterface
     */
    protected $platform;

    /**
     * Object attributes
     *
     * @var array
     */
    protected $_data = [];

    /**
     * Initialize Adapter
     *
     * @param array $file
     * @param Directory $directory
     * @param PlatformInterface $platform
     * @param array $data
     * @throws Exception
     */
    public function __construct(
        $file,
        Directory $directory,
        PlatformInterface $platform = null,
        $data = []
    ) {
        $this->_data = $data;
        $this->platform = $platform;
        $file = $directory->getAbsolutePath($file);
        $this->reader = ReaderFactory::createFromFile($file);
        $this->reader->open($file);

        $sheetIterator = $this->reader->getSheetIterator();
        $sheetIterator->rewind();
        $sheet = $sheetIterator->current();
        if (isset($data['xlsx_sheet']) && $data['xlsx_sheet'] !== 'undefined') {
            foreach ($sheetIterator as $sheetIt) {
                if (!isset($data['xlsx_sheet']) || $data['xlsx_sheet'] === '') {
                    $xlsxSheet = 1;
                } else {
                    $xlsxSheet = $data['xlsx_sheet'];
                }
                if ($sheetIt->getIndex() == $xlsxSheet - 1) {
                    $sheet = $sheetIt;
                    break;
                }
            }
        }
        $this->rowIterator = $sheet->getRowIterator();

        register_shutdown_function([$this, 'destruct']);

        $this->rewind();
        try {
            $originalData = $this->_getNextRow();
            $parseData = $platform && method_exists($platform, 'prepareData')
                ? $platform->prepareData($originalData)
                : $originalData;
        } catch (Exception $e) {
            throw $e;
        }
        parent::__construct(
            $parseData
        );
    }

    /**
     * Rewind the \Iterator to the first element (\Iterator interface)
     *
     * @return void
     * @throws IOException
     * @throws SharedStringNotFoundException
     */
    public function rewind()
    {
        $this->_key = -1;
        $this->_row = [];
        $this->rowIterator->rewind();
        if ($this->_colQty) {
            $this->next();
        }
    }

    /**
     * Move forward to next element (\Iterator interface)
     *
     * @return void
     * @throws IOException
     * @throws SharedStringNotFoundException
     */
    public function next()
    {
        $this->_key++;
        $this->rowIterator->next();
        $row = $this->_getNextRow();
        if (false === $row || [] === $row) {
            $this->_row = [];
            $this->_key = -1;
        } else {
            $this->_row = $row;
        }
    }

    /**
     * Render next row
     *
     * @return array|false
     */
    protected function _getNextRow()
    {
        return $this->rowIterator->current()->toArray();
    }

    /**
     * Return the key of the current element (\Iterator interface)
     *
     * @return int -1 if out of bounds, 0 or more otherwise
     */
    public function key()
    {
        return $this->_key;
    }

    /**
     * @return array|false|mixed
     */
    public function current()
    {
        $row = $this->rowIterator->current()->toArray();
        $valid = true;
        $emptyRow = 0;
        foreach ($row as $item) {
            if (empty($item)) {
                $emptyRow++;
            }
        }
        if ($emptyRow === count($row)) {
            $valid = false;
        }
        if (!$valid || count($row) != $this->_colQty) {
            if ($this->_foundWrongQuoteFlag) {
                throw new InvalidArgumentException(AbstractEntity::ERROR_CODE_WRONG_QUOTES);
            }

            if (!$valid) {
                throw new InvalidArgumentException(__('Empty Rows Detected'));
            }

            if ($this->_colQty > count($row)) {
                $row = $row + array_fill(count($row), $this->_colQty - count($row), '');
            } else {
                throw new InvalidArgumentException(AbstractEntity::ERROR_CODE_COLUMNS_NUMBER);
            }
        }
        $array = array_combine($this->_colNames, $row);

        $array = $this->replaceValue($this->changeFields($array));

        return $array;
    }

    /**
     * Checks if current position is valid (\Iterator interface)
     *
     * @return bool
     */
    public function valid()
    {
        return -1 !== $this->_key && $this->rowIterator->valid();
    }

    /**
     * Column names getter
     *
     * @return array
     */
    public function getColNames()
    {
        return $this->replaceColumns($this->_colNames);
    }

    /**
     * Return Platform
     *
     * @return mixed
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * Set Platform
     *
     * @param $platform
     * @return $this
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;
        return $this;
    }

    /**
     * Close file handle
     *
     * @return void
     */
    public function destruct()
    {
        if (is_object($this->reader)) {
            $this->reader->close();
        }
    }
}
