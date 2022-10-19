<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Translation\Google;

use Firebear\ImportExport\Model\Translation\TranslateAbstract;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use function html_entity_decode;

/**
 * Class Paid
 * @package Firebear\ImportExport\Model\Translation\Google
 */
class Paid extends Free
{
    /** @var string */
    public $url = 'https://www.googleapis.com/language/translate/v2';

    /** @var array */
    public $params = [];
    /** @var string */
    public $referer = '';

    /**
     * @param array $params
     * @return $this|TranslateAbstract
     */
    public function setTranslate(array $params)
    {
        if (isset($params['translate_from']) && 0 > strlen($params['translate_from'])) {
            $this->params['source'] = $params['translate_from'] ?? '';
        }
        $this->params['target'] = $params['translate_to'] ?? '';
        $this->params['key'] = $params['translate_key'] ?? '';
        $this->referer = $params['translate_referer'] ?? '';
        return $this;
    }

    /**
     * @return bool|string
     * @throws NoSuchEntityException
     */
    public function getResult()
    {
        $text = '';
        $targetData = $this->getTranslationResult();
        if ($targetData != '' && isset($targetData['data'])) {
            foreach ($targetData['data']['translations'] as $translation) {
                $text = html_entity_decode($translation['translatedText']);
            }
            return $text;
        } elseif (isset($targetData['error'])) {
            if (stripos($targetData['error']['message'], 'referer') !== false) {
                if ($this->referer) {
                    $this->setError(
                        __(
                            'Please check referer url on https://console.cloud.google.com to be %1 and type is HTTP',
                            $this->getBaseUrl()
                        )
                    );
                }
                $this->setError(
                    __('Please select referer type HTTP from import job form')
                );
            }
        }
        return false;
    }

    /**
     * @return string
     */
    public function getPostParams()
    {
        return array_merge_recursive($this->postParams, $this->params);
    }

    /**
     * @return string
     */
    public function getFullUrl()
    {
        return $this->url;
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    protected function getCurlOptions()
    {
        $options = parent::getCurlOptions();
        if ($this->referer) {
            $options[CURLOPT_REFERER] = $this->getBaseUrl();
        }
        return $options;
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    private function getBaseUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB);
    }
}
