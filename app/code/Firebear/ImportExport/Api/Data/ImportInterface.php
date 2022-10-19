<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Api\Data;

/**
 * Interface ImportInterface
 *
 * @package Firebear\ImportExport\Api\Data
 */
interface ImportInterface extends AbstractInterface
{
    const IMPORT_SOURCE = 'import_source';

    const MAP = 'map';

    const REPLACING = 'replacing';

    const XSLT = 'xslt';

    const TRANSLATE_FROM = "translate_from";

    const TRANSLATE_TO = "translate_to";

    const POSITION = 'position';

    /**
     * Get Import Source
     *
     * @return mixed
     */
    public function getImportSource();

    /**
     * @return string
     */
    public function getMapping();

    /**
     * @return string
     */
    public function getPriceRules();

    /**
     * @return string
     */
    public function getXslt();

    /**
     * Retrieve job position in the chain
     *
     * @return int|null
     */
    public function getPosition();

    /**
     * @param string $source
     *
     * @return ImportInterface
     */
    public function setImportSource($source);

    /**
     * @param $mapping
     *
     * @return ImportInterface
     */
    public function setMapping($mapping);

    /**
     * @param $priceRules
     *
     * @return ImportInterface
     */
    public function setPriceRules($priceRules);

    /**
     * @param $xslt
     *
     * @return ImportInterface
     */
    public function setXslt($xslt);

    /**
     * @return mixed
     */
    public function getTranslateFrom();

    /**
     * @return mixed
     */
    public function getTranslateTo();
    /**
     * @param $val
     *
     * @return mixed
     */
    public function setTranslateFrom($val);

    /**
     * @param $val
     *
     * @return mixed
     */
    public function setTranslateTo($val);

    /**
     * @return mixed
     */
    public function getMap();

    /**
     * Set job position in the chain
     *
     * @param int|null $position
     * @return $this
     */
    public function setPosition($position);
}
