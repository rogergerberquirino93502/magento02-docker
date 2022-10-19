<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Translation\Google;

use Firebear\ImportExport\Model\Translation\TranslateAbstract;

/**
 * Class Free
 * @link https://github.com/statickidz/php-google-translate-free/blob/master/src/GoogleTranslate.php
 * @package Firebear\ImportExport\Model\Translation\Google
 */
class Free extends TranslateAbstract
{
    /** @var string */
    public $url = 'https://translate.google.com/translate_a/single';

    /** @var array */
    public $params = [
        'client' => 'gtx', //system attribute
        'sl' => 'en', //source language
        'tl' => 'ru', //destination language
        'dt' => 't' //system attribute
    ];

    /** @var array */
    public $postParams = ['q' => ''];

    /**
     * @param array $params
     * @return $this|TranslateAbstract
     */
    public function setTranslate(array $params)
    {
        $this->params['sl'] = $params['translate_from'] ?? '';
        $this->params['tl'] = $params['translate_to'] ?? '';
        return $this;
    }

    /**
     * @return $this
     */
    public function setPostParams()
    {
        $this->postParams['q'] = $this->getText();
        return $this;
    }

    /**
     * @return array
     */
    protected function getCurlOptions()
    {
        return [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'
        ];
    }

    /**
     * @return bool|string
     */
    public function getResult()
    {
        $text = '';
        $bodyArray = $this->getTranslationResult();
        if (($bodyArray !== null) && isset($bodyArray[0])) {
            foreach ($bodyArray[0] as $rowText) {
                $text .= $rowText[0];
            }
            return $text;
        }
        return false;
    }
}
