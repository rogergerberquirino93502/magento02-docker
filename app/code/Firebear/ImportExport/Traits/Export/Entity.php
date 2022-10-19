<?php
declare(strict_types=1);
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Traits\Export;

use DateTime;
use Exception;
use Firebear\ImportExport\Traits\General as GeneralTrait;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Store\Model\Store;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\ImportExport\Model\Export;
use Magento\Eav\Model\Entity\Attribute;

/**
 * Trait Entity
 *
 * @package Firebear\ImportExport\Traits\Export
 */
trait Entity
{
    use GeneralTrait;

    /**
     * @var int
     */
    protected $lastEntityId;

    /**
     * @param array $data
     * @param string|null $entityFieldID
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function changeData($data, $entityFieldID = null)
    {
        $listCodes = $this->_parameters['list'];
        if ($entityFieldID) {
            $listCodes[] = $entityFieldID;
        }
        $replaces = $this->_parameters['replace_code'];
        $replacesValues = $this->_parameters['replace_value'];
        $newData = [];
        $allFields = $this->_parameters['all_fields'];
        foreach ($data as $record) {
            $newRecord = [];
            foreach ($record as $code => $value) {
                if (isset($this->_fieldsMap)) {
                    $code = $this->getKeyFromList($this->_fieldsMap, $code) ?: $code;
                }
                if (in_array($code, $listCodes)) {
                    $keyCode = $this->getKeyFromList($listCodes, $code);
                    $newCode = $code;
                    if (is_numeric($keyCode) && isset($replaces[$keyCode])) {
                        $newCode = $replaces[$keyCode];
                    }
                    $newRecord[$newCode] = $value;
                    if (isset($replacesValues[$keyCode]) && $replacesValues[$keyCode] !== '') {
                        $newRecord[$newCode] = $replacesValues[$keyCode];
                    }
                } else {
                    if (!$allFields) {
                        $newRecord[$code] = $value;
                    }
                }
            }

            $noFullList = array_diff($listCodes, array_keys($newRecord));
            if (!empty($noFullList)) {
                $newCode = '';
                foreach ($noFullList as $code => $value) {
                    if (isset($replaces[$code])) {
                        $newCode = $replaces[$code];
                    }
                    if (isset($replacesValues[$code])
                        && $replacesValues[$code] !== ''
                        && !isset($newRecord[$newCode])
                    ) {
                        $newRecord[$newCode] = $replacesValues[$code];
                    }
                    $newRecord[$code] = $value;
                }
            }
            if (!empty($newRecord)) {
                $newData[] = $newRecord;
            }
        }

        return $newData ? $newData : $data;
    }

    /**
     * @param array $list
     * @param string $search
     * @return false|int|string
     */
    protected function getKeyFromList($list, $search)
    {
        return array_search($search, $list);
    }

    /**
     * @param array $row
     * @return array
     */
    public function changeRow($row)
    {
        $listCodes = $this->_parameters['list'];
        $replaces = $this->_parameters['replace_code'];
        $allFields = $this->_parameters['all_fields'];
        $replacesValues = $this->_parameters['replace_value'];
        $newRecord = [];
        foreach ($row as $code => $value) {
            if (in_array($code, $listCodes)) {
                $keyCode = $this->getKeyFromList($listCodes, $code);
                $newCode = $code;
                if (isset($replaces[$keyCode])) {
                    $newCode = $replaces[$keyCode];
                }
                $newRecord[$newCode] = is_array($value) ? json_encode($value) : $value;
                if (isset($replacesValues[$keyCode]) && !empty($replacesValues[$keyCode])) {
                    $newRecord[$newCode] = $replacesValues[$keyCode];
                }
            } else {
                if (!$allFields) {
                    $newRecord[$code] = is_array($value) ? json_encode($value) : $value;
                }
            }
        }

//        $noFullList = array_diff($listCodes, array_keys($newRecord));
//        if (!empty($noFullList)) {
//            foreach ($noFullList as $code => $value) {
//                $newRecord[$code] = $value;
//            }
//        }

        return $newRecord;
    }

    /**
     * @param array $headers
     * @return array
     */
    public function changeHeaders($headers)
    {
        $allFields = $this->_parameters['all_fields'];
        $listCodes = $this->_parameters['list'];
        $countCodes = count($listCodes);
        $replaces = $this->_parameters['replace_code'];
        $newHeaders = [];
        foreach ($headers as $code) {
            if (in_array($code, $listCodes)) {
                $newCode = $code;
                $keyCode = $this->getKeyFromList($listCodes, $code);
                if (isset($replaces[$keyCode])) {
                    $newCode = $replaces[$keyCode];
                    $newHeaders[array_search($code, $listCodes)] = $newCode;
                } else {
                    $newHeaders[$countCodes++] = $newCode;
                }
            } else {
                if (!$allFields) {
                    $newHeaders[$countCodes++] = $code;
                }
            }
        }
        ksort($newHeaders);

        return $newHeaders ? $newHeaders : $headers;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        /** @var AbstractDb $entityCollection */
        $entityCollection = $this->_getEntityCollection();
        return $entityCollection->getSize();
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->_parameters;
    }

    /**
     * Apply filter to collection and add not skipped attributes to select.
     *
     * @param AbstractCollection $collection
     * @return AbstractCollection
     * @throws LocalizedException
     * @throws Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _prepareEntityCollection(AbstractCollection $collection)
    {
        if (!isset($this->_parameters[Export::FILTER_ELEMENT_GROUP])
            || !is_array($this->_parameters[Export::FILTER_ELEMENT_GROUP])
        ) {
            $filter = [];
        } else {
            $filter = $this->_parameters[Export::FILTER_ELEMENT_GROUP];
        }

        $exportCodes = $this->_getExportAttrCodes();

        /** @var Attribute $attribute */
        foreach ($this->filterAttributeCollection($this->getAttributeCollection()) as $attribute) {
            $attrCode = $attribute->getAttributeCode();

            // filter applying
            if (isset($filter[$attrCode])) {
                $attrFilterType = Export::getAttributeFilterType($attribute);

                if (Export::FILTER_TYPE_SELECT == $attrFilterType) {
                    if (is_scalar($filter[$attrCode])) {
                        if ($filter[$attrCode] == 0) {
                            $collection->addAttributeToFilter([
                                ['attribute' => $attrCode, 'eq' => $filter[$attrCode]],
                                ['attribute' => $attrCode, 'null' => 1],
                            ]);
                        } else {
                            $collection->addAttributeToFilter($attrCode, ['eq' => $filter[$attrCode]]);
                        }
                    }
                } elseif (Export::FILTER_TYPE_INPUT == $attrFilterType) {
                    if (is_scalar($filter[$attrCode]) && trim($filter[$attrCode])) {
                        $collection->addAttributeToFilter($attrCode, ['like' => "%{$filter[$attrCode]}%"]);
                    }
                } elseif (Export::FILTER_TYPE_DATE == $attrFilterType) {
                    if (is_array($filter[$attrCode]) && count($filter[$attrCode]) == 2) {
                        $from = array_shift($filter[$attrCode]);
                        $to = array_shift($filter[$attrCode]);

                        if (is_scalar($from) && !empty($from)) {
                            $date = (new DateTime($from))->format('m/d/Y');
                            $collection->addAttributeToFilter($attrCode, ['from' => $date, 'date' => true]);
                        }
                        if (is_scalar($to) && !empty($to)) {
                            $date = (new DateTime($to))->format('m/d/Y');
                            $collection->addAttributeToFilter($attrCode, ['to' => $date, 'date' => true]);
                        }
                    }
                } elseif (Export::FILTER_TYPE_NUMBER == $attrFilterType) {
                    if (is_array($filter[$attrCode]) && count($filter[$attrCode]) == 2) {
                        $from = array_shift($filter[$attrCode]);
                        $to = array_shift($filter[$attrCode]);

                        if (is_numeric($from)) {
                            $collection->addAttributeToFilter(
                                $attrCode,
                                ['from' => $from]
                            );
                        }
                        if (is_numeric($to)) {
                            $collection->addAttributeToFilter(
                                $attrCode,
                                ['to' => $to]
                            );
                        }
                    }
                }
            }
            if (in_array($attrCode, $exportCodes)) {
                $collection->addAttributeToSelect($attrCode);
            }
        }
        return $collection;
    }

    /**
     * Retrieve entity field for export
     *
     * @return array
     */
    public function getFieldsForExport()
    {
        return [];
    }

    /**
     * Retrieve entity field for filter
     *
     * @return array
     */
    public function getFieldsForFilter()
    {
        return [];
    }

    /**
     * Retrieve store ids for filter
     *
     * @return array
     */
    public function getStoreIdsForFilter()
    {
        if (!empty($this->_parameters['only_admin'])) {
            return [Store::DEFAULT_STORE_ID];
        }
        return $this->_parameters['behavior_data']['store_ids'] ?? [];
    }

    /**
     * Retrieve entity field columns
     *
     * @return array
     */
    public function getFieldColumns()
    {
        return [];
    }

    /**
     * Retrieve entity attribute type
     *
     * @param $type
     * @return string
     */
    private function getAttributeType($type)
    {
        if (in_array($type, ['int', 'decimal', 'price'])) {
            return 'int';
        }
        if (in_array($type, ['varchar', 'text', 'textarea'])) {
            return 'text';
        }
        if (in_array($type, ['select', 'multiselect', 'boolean'])) {
            return 'select';
        }
        if (in_array($type, ['datetime', 'date'])) {
            return 'date';
        }
        return 'not';
    }
}
