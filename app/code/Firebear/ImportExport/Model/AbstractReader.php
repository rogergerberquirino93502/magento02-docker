<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model;

use Magento\Framework\Config\Reader\Filesystem;

/**
 * Class AbstractReader
 *
 * @package Firebear\ImportExport\Model
 */
class AbstractReader extends Filesystem
{
    /**
     * @var array
     */
    protected $_idAttributes = [
        '/config/type' => 'name'
    ];
}
