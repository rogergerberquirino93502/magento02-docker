<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Api\Data;

/**
 * Interface ExportInterface
 *
 * @package Firebear\ImportExport\Api\Data
 */
interface ExportInterface extends AbstractInterface
{
    const XSLT = 'xslt';

    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
     const EXPORT_SOURCE = 'export_source';

     const EVENT = 'event';
    /**
     * @return mixed[]
     */
    public function getExportSource();

    /**
     *
     * @return string
     */
    public function getXslt();

    /**
     * @param mixed[] $source
     *
     * @return ExportInterface
     */
    public function setExportSource($source);

    /**
     * @param $xslt
     *
     * @return string
     */
    public function setXslt($xslt);

    /**
     * @return mixed
     */
    public function getEvent();
}
