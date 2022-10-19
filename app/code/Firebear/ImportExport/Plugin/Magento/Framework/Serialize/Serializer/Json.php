<?php
/**
 * Copyright © Firebear Studio. All rights reserved. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Firebear\ImportExport\Plugin\Magento\Framework\Serialize\Serializer;

/**
 * @see issue II-1155
 */
class Json
{

    /**
     * @param \Magento\Framework\Serialize\Serializer\Json $subject
     * @param \Closure $proceed
     * @param $string
     * @return array|mixed
     */
    public function aroundUnserialize(
        \Magento\Framework\Serialize\Serializer\Json $subject,
        \Closure $proceed,
        $string
    ) {
        if ($string === '') {
            $jsonDecode = [];
        } else {
            $jsonDecode = $proceed($string);
        }
        return $jsonDecode;
    }

    /**
     * @param \Magento\Framework\Serialize\Serializer\Json $subject
     * @param \Closure $proceed
     * @param $data
     * @return mixed|string
     */
    public function aroundSerialize(
        \Magento\Framework\Serialize\Serializer\Json $subject,
        \Closure $proceed,
        $data
    ) {
        $jsonString = '';
        try {
            $jsonString = $proceed($data);
        } catch (\Exception $exception) {
            if (json_last_error_msg() === 'Malformed UTF-8 characters, possibly incorrectly encoded') {
//                $data = $this->convert_to_utf8_recursively($data);
                $data = array_map([$this,'convertToUtf8Recursively'], $data);
                $jsonString = $proceed($data);
            }
        }
        return $jsonString;
    }

    /**
     * @param $dat
     * @return array|false|string|string[]|null
     */
    private function convertToUtf8Recursively($dat)
    {
        if (is_string($dat)) {
            return mb_convert_encoding($dat, 'UTF-8', 'UTF-8');
        } elseif (is_array($dat)) {
            $ret = [];
            foreach ($dat as $i => $d) {
                $ret[$i] = $this->convertToUtf8Recursively($d);
            }
            return $ret;
        } else {
            return $dat;
        }
    }
}
