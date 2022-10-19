<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Translation;

/**
 * Interface TranslateInterface
 * @package Firebear\ImportExport\Model\Translation
 */
interface TranslateInterface
{
    /**
     * @param string $text
     * @param array $params
     * @return string or false
     */
    public static function translate(string $text, array $params = []);

    /**
     * @param array $params
     * @return $this
     */
    public function setTranslate(array $params);

    /**
     * @return $this
     */
    public function setPostParams();

    /**
     * @return bool|string
     */
    public function getResult();

    /**
     * @return $this
     */
    public function run();
}
