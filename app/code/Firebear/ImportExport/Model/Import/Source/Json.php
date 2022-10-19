<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Source;

use Magento\Framework\Filesystem\Directory\Read as Directory;
use Magento\ImportExport\Model\Import\AbstractEntity;
use Magento\ImportExport\Model\Import\AbstractSource;
use Firebear\ImportExport\Model\Source\Platform\PlatformInterface;
use Firebear\ImportExport\Traits\Import\Map as ImportMap;

/**
 * CSV import adapter
 */
class Json extends AbstractSource
{
    use ImportMap;

    const CREATE_ATTRIBUTE = 'create_attribute';

    /**
     * @var Resource
     */
    protected $stream;

    /**
     * @var \JsonStreamingParser\Listener\InMemoryListener $listener
     */
    protected $listener;

    protected $maps;

    protected $extension = 'json';

    protected $mimeTypes = [];

    /**
     * Platform
     *
     * @var \Firebear\ImportExport\Model\Source\Platform\PlatformInterface
     */
    protected $platform;

    protected $entities;

    /**
     * Iterator Lock Flag
     *
     * @var bool
     */
    protected $_lock = false;

    /**
     * Prepared Items
     *
     * @var array
     */
    protected $_items = [];

    /**
     * Initialize Adapter
     *
     * @param array $file
     * @param Directory $directory
     * @param PlatformInterface $platform
     * @param array $data
     * @throws \Exception
     */
    public function __construct(
        $file,
        Directory $directory,
        PlatformInterface $platform = null,
        $data = []
    ) {
        $result = $this->checkMimeType($directory->getAbsolutePath($file));
        if ($result !== true) {
            throw new \RuntimeException($result);
        }

        $this->platform = $platform;
        $this->listener = new \JsonStreamingParser\Listener\InMemoryListener();
        try {
            $this->stream = fopen($directory->getAbsolutePath($file), 'r');
            $parser = new \JsonStreamingParser\Parser($this->stream, $this->listener);
            $parser->parse();
            fclose($this->stream);

            $data = $this->listener->getJson();
            $parseData = $platform && method_exists($platform, 'prepareData')
                ? $platform->prepareData($data)
                : $data;
            $finalData = false;
            foreach ($parseData as $datum) {
                if (\is_array($datum)) {
                    $finalData = $datum;
                    break;
                }
            }
            $this->entities = $finalData ?? [];
        } catch (\Exception $e) {
            fclose($this->stream);
            throw $e;
        }

        $this->_colNames = array_keys($this->entities[0] ?? []);
        parent::__construct(
            $this->_colNames
        );
    }

    /**
     * Close file handle
     *
     * @return void
     */
    public function destruct()
    {
        fclose($this->stream);
    }

    /**
     * Read next line from JSON-file
     *
     * @return array|bool
     */
    protected function _getNextRow()
    {
        $parsed = [];
        /* reader prepared items */
        if ($this->_lock) {
            foreach ($this->_items as $field => $items) {
                foreach ($items as $key => $item) {
                    $parsed[$field] = $item;
                    unset($this->_items[$field][$key]);
                    break;
                }
                if (0 == count($this->_items[$field])) {
                    unset($this->_items[$field]);
                }
                break;
            }
            if (0 == count($this->_items)) {
                $this->_items = [];
                $this->_lock = false;
            }
            return $parsed;
        } elseif (count($this->entities)) {
            $fields = array_shift($this->entities);
            foreach ($fields as $name => $data) {
                if ($name == self::CREATE_ATTRIBUTE) {
                    $text = 'attribute';
                    $valueText = '';
                    foreach ($data as $nameAttribute => $valueAttribute) {
                        if ($nameAttribute != 'value') {
                            $text .= "|" . $nameAttribute . ":" . $valueAttribute;
                        } else {
                            $valueText = $valueAttribute;
                        }
                    }
                    $parsed[$text] = $valueText;
                    continue;
                } elseif (is_array($data)) { /* prepare child children */
                    $this->_items[$name] = [];
                    foreach ($data as $childItem) {
                        $item = [];
                        foreach ($childItem as $field => $fieldValue) {
                            if (is_array($fieldValue)) {
                                $subKey = $name . '_' . $field;
                                $this->_items[$subKey] = [];
                                foreach ($fieldValue as $subItemField) {
                                    $subItem = [];
                                    foreach ($subItemField as $subField => $subFieldValue) {
                                        $subItem[$subField] = $subFieldValue;
                                    }
                                    $this->_items[$subKey][] = $subItem;
                                }
                                $item[$field] = '';
                            } else {
                                $item[$field] = (string)$fieldValue;
                            }
                        }
                        $this->_items[$name][] = $item;
                    }
                    $value = '';
                } else {
                    $value = (string)$data;
                    if (is_string($value) && strpos($value, "'") !== false) {
                        $this->_foundWrongQuoteFlag = true;
                    }
                }
                $parsed[$name] = $value;
            }
            $this->_lock = (0 < count($this->_items)) ? true : false;
            return $parsed;
        } else {
            return false;
        }
    }

    /**
     * Rewind the \Iterator to the first element (\Iterator interface)
     *
     * @return void
     */
    public function rewind()
    {
        $this->_items = [];
        $this->_lock = false;
        parent::rewind();
    }

    /**
     * Return the current element
     *
     * @return array
     */
    public function current()
    {
        return $this->replaceValue(
            $this->changeFields($this->_row)
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
     * @param $platform
     * @return $this
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPlatform()
    {
        return $this->platform;
    }
}
