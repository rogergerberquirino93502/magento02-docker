<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Traits\Import;

use Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Replace\Target\Options;
use Magento\Framework\Exception\LocalizedException;
use Firebear\ImportExport\Api\Data\JobReplacingInterface;
use Firebear\ImportExport\Model\Import\Attribute;

/**
 * Trait ReplacingTrait
 * @package Firebear\ImportExport\Traits\Import
 */
trait ReplacingTrait
{
    /** @var array */
    protected $replacing = [];

    /**
     * [
     *  ...,
     *  (int)[replacing.entity_id] => [
     *      'attribute_code'    => (string),
     *      'target'            => (int),
     *      'is_case_sensitive' => (bool),
     *      'find'              => (string),
     *      'replace'           => (string),
     *  ],
     *  ...
     * ]
     * @return array
     */
    public function getReplacing()
    {
        return $this->replacing;
    }

    /**
     * @param array $data
     * @return $this
     * @throws LocalizedException
     */
    public function setReplacing(array $data)
    {
        $this->replacing = $this->validateReplacings($data);
        return $this;
    }

    /**
     * @param array $rowData
     * @return array
     */
    protected function handleReplacing($rowData)
    {
        foreach ($this->getReplacing() as $replacing) {
            $rowData = $this->modify($rowData, $replacing);
        }
        return $rowData;
    }

    /**
     * Fast check replacings array structure
     *
     * @param array $data
     * @return array
     * @throws LocalizedException
     */
    private function validateReplacings(array $data)
    {
        $keys = array_flip($this->getReplacingKeys());
        foreach ($data as $replacing) {
            if (array_diff_key($keys, $replacing)) {
                throw new LocalizedException(__('Replacing record has wrong structure'));
            }
        }
        return $data;
    }

    /**
     * @param array $rowData
     * @param array $replacing
     * @return array
     */
    private function modify(array $rowData, array $replacing)
    {
        $code = $replacing['attribute_code'];
        if (!isset($rowData[$code])) {
            return $rowData;
        }
        $value = $rowData[$code];
        $isCaseSensitive = (bool) $replacing['is_case_sensitive'];
        $find = (string) trim($replacing['find']);
        $replace = (string) $replacing['replace'];
        switch ((int) $replacing['target']) {
            case Options::INDIVIDUAL_WORD:
                $value = $this->modifyWord($value, $find, $replace, $isCaseSensitive);
                break;
            case Options::FULL_VALUE:
                $value = $this->modifyFull($value, $find, $replace, $isCaseSensitive);
                break;
        }
        $rowData[$code] = $value;
        return $rowData;
    }

    /**
     * In this case we need replace only one word if it matched
     * Example:
     * Target: Individual Word
     * Attribute: description
     * Find: fox
     * Replace: bear
     * String: ‘The fox runs really fast’
     * Result: ‘The bear runs really fast’
     *
     * @param string $value
     * @param string $find
     * @param string $replace
     * @param bool $isCaseSensitive
     * @return string
     */
    private function modifyWord(string $value, string $find, string $replace, bool $isCaseSensitive)
    {
        return $isCaseSensitive ?
            str_replace($find, $replace, $value) : str_ireplace($find, $replace, $value);
    }

    /**
     * In this case we need full replace if matched string was found
     * Example:
     * Target: Full Value
     * Attribute: description
     * Find: fox
     * Replace: ‘Bears like to climb trees’
     * String: ‘The fox runs really fast’
     * Result: ‘Bears like to climb trees’
     * Why was the full sentence replaced?
     * The Target was set to Full Value and since the word ‘fox’ was matched, it replaced the full string
     *
     * @param string $value
     * @param string $find
     * @param string $replace
     * @param bool $isCaseSensitive
     * @return string
     */
    private function modifyFull(string $value, string $find, string $replace, bool $isCaseSensitive)
    {
        $matched = $isCaseSensitive ? mb_strpos($value, $find) : mb_stripos($value, $find);
        return $matched === false ? $value : $replace;
    }

    /**
     * Structure keys
     *
     * @return array
     */
    private function getReplacingKeys()
    {
        return [
            JobReplacingInterface::ATTRIBUTE_CODE,
            JobReplacingInterface::TARGET,
            JobReplacingInterface::IS_CASE_SENSITIVE,
            JobReplacingInterface::FIND,
            JobReplacingInterface::REPLACE
        ];
    }
}
