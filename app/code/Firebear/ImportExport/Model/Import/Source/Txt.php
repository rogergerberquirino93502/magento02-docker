<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Source;

/**
 * Txt import adapter
 */
class Txt extends \Firebear\ImportExport\Model\Import\Source\Csv
{
    use \Firebear\ImportExport\Traits\Import\Map;

    protected $extension = 'txt';

    /**
     * Read next line from CSV-file
     *
     * @return array|bool
     */
    protected function _getNextRow()
    {
        try {
            $parsed = preg_split("/" . $this->delimiter . "/", $this->file->readLine(0, "\n"));
        } catch (\Exception $e) {
            $parsed = false;
        }

        $checkerEnclosure = false;
        $resultArray = [];
        if (is_array($parsed) && count($parsed) != $this->_colQty) {
            $key = 0;
            $partOneKey = false;
            $partTwoKey = false;
            $beginKey = 0;
            for ($i = 0; $i < count($parsed); $i++) {
                if ($this->_colQty && $key >= $this->_colQty && $partOneKey == false) {
                    continue;
                }
                $item = $parsed[$i];
                if (strpos($item, $this->enclosure) !== false && strlen($item) == 1) {
                    $resultArray[$key] = $item;
                    $key++;
                    continue;
                }
                if ((strpos($item, $this->enclosure) === 0 || strrpos($item, $this->enclosure) === strlen($item) - 1)
                    && !(strpos($item, $this->enclosure) === 0 &&
                        strrpos($item, $this->enclosure) === strlen($item) - 1)) {
                    if ($checkerEnclosure === false) {
                        if (!$partOneKey) {
                            $partOneKey = $i;
                            $beginKey = $key;
                        } else {
                            $partTwoKey = $i;
                        }
                        if ($partOneKey && $partTwoKey) {
                            $checkerEnclosure = true;
                        }
                    } else {
                        $checkerEnclosure = false;
                    }
                } else {
                    $checkerEnclosure = false;
                }

                if ($checkerEnclosure !== false) {
                    $str = $parsed[$partOneKey];
                    for ($j = $partOneKey + 1; $j <= $partTwoKey; $j++) {
                        $str .= $this->delimiter . $parsed[$j];
                    }

                    $resultArray[$beginKey] = $str;
                    $resultArray = array_slice($resultArray, 0, $beginKey + 1);
                    $key = $beginKey;
                    $beginKey = 0;
                    $partOneKey = false;
                    $partTwoKey = false;
                    $checkerEnclosure = false;
                } else {
                    $resultArray[$key] = $item;
                }

                $key++;
            }
        }

        return $resultArray ? $this->removeEnclosure($resultArray) : $this->removeEnclosure($parsed);
    }

    /**
     * @param $array
     * @return mixed
     */
    protected function removeEnclosure($array)
    {
        if (!is_array($array)) {
            return $array;
        }

        foreach ($array as &$item) {
            $item = str_replace('"', "", $item);
        }

        return $array;
    }
}
