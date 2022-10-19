<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Config;

/**
 * Class SourceReader
 *
 * @package Firebear\ImportExport\Model\Source\Config
 */
class SourceReader extends \Firebear\ImportExport\Model\AbstractReader
{
    /**
     * @var array
     */
    protected $_idAttributes = [
        '/config/fileFormat' => ['name', 'direction']
    ];
}
