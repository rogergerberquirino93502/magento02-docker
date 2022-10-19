<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Translation;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\StoreManagerInterface;
use RuntimeException;

/**
 * Class TranslateAbstract
 * @package Firebear\ImportExport\Model\Translation
 */
abstract class TranslateAbstract implements TranslateInterface
{
    /** @var string */
    public $result;
    /** @var string */
    public $text;
    /** @var string */
    public $url;
    /** @var array */
    public $params = [];
    /** @var array */
    public $postParams = [];
    /**
     * @var SerializerInterface
     */
    protected $serializer;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var Curl
     */
    private $curl;

    /**
     * TranslateAbstract constructor.
     * @param Curl $curl
     * @param SerializerInterface $serializer
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Curl $curl,
        SerializerInterface $serializer,
        StoreManagerInterface $storeManager
    ) {
        $this->curl = $curl;
        $this->serializer = $serializer;
        $this->storeManager = $storeManager;
    }

    /**
     * @param string $text
     * @param array $params
     * @return string or false
     */
    public static function translate(string $text, array $params = [])
    {
        $objectManager = ObjectManager::getInstance();
        $curl = $objectManager->get(Curl::class);
        $serialize = $objectManager->get(SerializerInterface::class);
        $storeManager = $objectManager->get(StoreManagerInterface::class);
        /** @phpstan-ignore-next-line */
        $translateClass = (new static($curl, $serialize, $storeManager));
        return $translateClass
            ->setTranslate($params)
            ->prepareText($text)
            ->run();
    }

    /**
     * @return mixed
     */
    public function run()
    {
        return $this->setPostParams()->setParams()->sendPost()->getResult();
    }

    /**
     * @return bool|string
     */
    abstract public function getResult();

    /**
     * Send request to Google Translate
     * @return $this
     */
    public function sendPost()
    {
        $this->curl->setOptions($this->getCurlOptions());
        $this->curl->post($this->getFullUrl(), $this->getPostParams());
        $this->result = $this->curl->getBody();
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
        ];
    }

    /**
     * @return string
     */
    protected function getFullUrl()
    {
        return $this->url . '?' . http_build_query($this->getParams());
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @return $this
     */
    public function setParams()
    {
        return $this;
    }

    /**
     * @return string
     */
    public function getPostParams()
    {
        return urldecode(http_build_query($this->postParams));
    }

    /**
     * @return $this
     */
    abstract public function setPostParams();

    /**
     * Sanitize text
     * @param string $text
     *
     * @return $this
     */
    public function prepareText(string $text)
    {
        $this->text = $text;
        return $this;
    }

    /**
     * @param array $params
     * @return $this
     */
    abstract public function setTranslate(array $params);

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param $msg
     */
    public function setError($msg)
    {
        throw new RuntimeException($msg);
    }

    /**
     * @return array|bool|float|int|string|null
     */
    public function getTranslationResult()
    {
        return $this->serializer->unserialize($this->result);
    }
}
