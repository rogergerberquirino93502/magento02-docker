<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Import\Source;

use OpenSpout\Reader\ReaderInterface;
use OpenSpout\Reader\Common\Creator\ReaderFactory;
use Magento\Framework\Filesystem\Directory\Read as Directory;
use Magento\ImportExport\Model\Import\AbstractEntity;
use Magento\ImportExport\Model\Import\AbstractSource;
use Firebear\ImportExport\Model\Source\Platform\PlatformInterface;
use Firebear\ImportExport\Traits\Import\Map as ImportMap;

/**
 * Ods Import Adapter
 */
class Ods extends AbstractSource
{
    use ImportMap;

    private static $isAliasCreated = false;

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
    protected $extension = 'ods';

    /**
     * Platform
     *
     * @var \Firebear\ImportExport\Model\Source\Platform\PlatformInterface
     */
    protected $platform;

    /**
     * Initialize Adapter
     *
     * @param array $file
     * @param Directory $directory
     * @param PlatformInterface $platform
     * @throws \Exception
     */
    public function __construct(
        $file,
        Directory $directory,
        PlatformInterface $platform = null
    ) {
        $this->platform = $platform;
        $file = $directory->getAbsolutePath($file);
        $this->reader = ReaderFactory::createFromFile($file);
        $this->reader->open($file);

        $sheetIterator = $this->reader->getSheetIterator();
        $sheetIterator->rewind();
        $sheet = $sheetIterator->current();

        $this->rowIterator = $sheet->getRowIterator();

        register_shutdown_function([$this, 'destruct']);

        $this->rewind();
        try {
            $originalData = $this->_getNextRow();
            $parseData = $platform && method_exists($platform, 'prepareData')
                ? $platform->prepareData($originalData)
                : $originalData;
        } catch (\Exception $e) {
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
     */
    public function rewind()
    {
        $this->_key = 0;
        $this->_row = [];
        if (!$this->_colQty) {
            /**
             * Because sheet and row data is located in the file, we can't rewind both the
             * sheet iterator and the row iterator, as XML file cannot be read backwards.
             * Therefore, rewinding the row iterator has been disabled.
             * @see RowIterator
             */
            $this->rowIterator->rewind();
        }
        if ($this->_colQty) {
            $this->next();
        }
    }

    /**
     * Move forward to next element (\Iterator interface)
     *
     * @return void
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
     * Return the key of the current element (\Iterator interface)
     *
     * @return int -1 if out of bounds, 0 or more otherwise
     */
    public function key()
    {
        return $this->_key;
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
     * Render next row
     *
     * @return array|false
     */
    protected function _getNextRow()
    {
        return $this->rowIterator->current()->toArray();
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
                throw new \InvalidArgumentException(AbstractEntity::ERROR_CODE_WRONG_QUOTES);
            }

            if (!$valid) {
                throw new \InvalidArgumentException(__('Empty Rows Detected'));
            }

            if ($this->_colQty > count($row)) {
                $row = $row + array_fill(count($row), $this->_colQty - count($row), '');
            } else {
                throw new \InvalidArgumentException(AbstractEntity::ERROR_CODE_COLUMNS_NUMBER);
            }
        }
        $array = array_combine($this->_colNames, $row);

        $array = $this->replaceValue($this->changeFields($array));

        return $array;
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
     * Return Platform
     *
     * @return mixed
     */
    public function getPlatform()
    {
        return $this->platform;
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
