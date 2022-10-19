<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Filesystem\Driver;

use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class Http
 *
 * @package Firebear\ImportExport\Model\Filesystem\Driver
 */
class Http extends \Magento\Framework\Filesystem\Driver\Http
{
    /**
     * @param string $path
     * @return bool
     */
    public function isExists($path)
    {
        $result = false;
        $headers = array_change_key_case(get_headers($this->getScheme() . $path, 1), CASE_LOWER);
        $keys = array_keys($headers);
        foreach ($keys as $key) {
            if (is_numeric($key)) {
                $status = $headers[$key];
                if (strpos($status, '200 OK') !== false) {
                    $result = true;
                }
            }
        }

        return $result;
    }
}
