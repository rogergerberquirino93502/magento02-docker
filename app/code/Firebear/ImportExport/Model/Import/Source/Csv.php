<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Source;

use Magento\Framework\Filesystem\Directory\Read as Directory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\ImportExport\Model\Import\AbstractEntity;
use Magento\ImportExport\Model\Import\AbstractSource;
use Magento\ImportExport\Model\Import;
use Firebear\ImportExport\Model\Source\Platform\PlatformInterface;
use Firebear\ImportExport\Traits\Import\Map as ImportMap;
use Firebear\ImportExport\Api\Data\SeparatorFormatterInterface;

/**
 * CSV import adapter
 */
class Csv extends AbstractSource
{
    use ImportMap;

    /**
     * @var \Magento\Framework\Filesystem\File\Write
     */
    protected $file;

    /**
     * Delimiter
     *
     * @var string
     */
    protected $delimiter = ',';

    /**
     * @var string
     */
    protected $enclosure = '"';

    protected $maps;

    protected $extension = 'csv';

    protected $mimeTypes = [
    ];

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
     * @param SeparatorFormatterInterface $separatorFormatter
     * @param PlatformInterface $platform
     * @param array $data
     *
     * @throws LocalizedException
     * @throws \LogicException
     * @throws \Exception
     */
    public function __construct(
        $file,
        Directory $directory,
        SeparatorFormatterInterface $separatorFormatter,
        PlatformInterface $platform = null,
        $data = []
    ) {
        register_shutdown_function([$this, 'destruct']);
        try {
            $result = $this->checkMimeType(
                $directory->getRelativePath($file)
            );
            if ($result !== true) {
                throw new LocalizedException($result);
            }
            $this->file = $directory->openFile(
                $directory->getRelativePath($file),
                'r'
            );
        } catch (FileSystemException $e) {
            throw new \LogicException("Unable to open file: '{$file}'");
        }

        $this->platform = $platform;
        $this->delimiter = $data[Import::FIELD_FIELD_SEPARATOR] ?? $$this->delimiter;
        $this->delimiter = $separatorFormatter->format($this->delimiter);
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
     * Close file handle
     *
     * @return void
     */
    public function destruct()
    {
        if (is_object($this->file)) {
            $this->file->close();
        }
    }

    /**
     * Checks if current position is valid (\Iterator interface)
     *
     * @return bool
     */
    public function valid()
    {
        return -1 !== $this->_key;
    }

    /**
     * Read next line from CSV-file
     *
     * @return array|bool
     */
    protected function _getNextRow()
    {
        $parsed = $this->file->readCsv(0, $this->delimiter, $this->enclosure);
        if (is_array($parsed) && count($parsed) != $this->_colQty) {
            foreach ($parsed as $element) {
                if (strpos($element, "'") !== false) {
                    $this->_foundWrongQuoteFlag = true;
                    break;
                }
            }
        } else {
            $this->_foundWrongQuoteFlag = false;
        }

        return is_array($parsed) ? $parsed : [];
    }

    /**
     * Rewind the \Iterator to the first element (\Iterator interface)
     *
     * @return void
     */
    public function rewind()
    {
        $this->file->seek(0);
        $this->_getNextRow();
        // skip first line with the header
        parent::rewind();
    }

    /**
     * @return array
     */
    public function current()
    {
        $row = $this->_row;
        if (count($row) != $this->_colQty) {
            $error = $this->_foundWrongQuoteFlag
                ? AbstractEntity::ERROR_CODE_WRONG_QUOTES
                : AbstractEntity::ERROR_CODE_COLUMNS_NUMBER;
            throw new \InvalidArgumentException($error);
        }

        $array = array_combine($this->_colNames, $row);
        return $this->replaceValue(
            $this->changeFields($array)
        );
    }

    /**
     * @return mixed
     */
    public function getColNames()
    {
        return $this->replaceColumns($this->_colNames);
    }

    /**
     * Set Platform
     *
     * @param PlatformInterface $platform
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
     * @return null|\Firebear\ImportExport\Model\Source\Platform\PlatformInterface
     */
    public function getPlatform()
    {
        return $this->platform;
    }
}
