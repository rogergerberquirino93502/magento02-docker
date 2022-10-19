<?php
/**
 * MediaHelper
 *
 * @copyright Copyright © 2019 Firebear Studio. All rights reserved.
 * @author    fbeardev@gmail.com
 */

namespace Firebear\ImportExport\Helper;

use Firebear\ImportExport\Model\Import\Uploader;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Serialize;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\ProductVideo\Helper\Media;
use RuntimeException;

/**
 * Class MediaHelper
 * @package Firebear\ImportExport\Helper
 */
class MediaHelper extends Media
{
    /** @var string */
    const YOUTUBE_URL = 'https://www.googleapis.com/youtube/v3/videos';

    /**
     * Part of the domain name.
     * We use part of the domain name, because the domain name can be like youtube.com or youtu.be
     *
     * @var string
     */
    const YOUTUBE = 'youtu';

    /** @var string  */
    const VIMEO = 'vimeo';

    /**
     * @var Serialize
     */
    private $serialize;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * MediaHelper constructor.
     * @param Context $context
     * @param Serialize $serialize
     * @param SerializerInterface $serializer
     * @param Curl $curl
     */
    public function __construct(
        Context $context,
        Serialize $serialize,
        SerializerInterface $serializer,
        Curl $curl
    ) {
        parent::__construct($context);
        $this->serialize = $serialize;
        $this->curl = $curl;
        $this->serializer = $serializer;
    }

    /**
     * @param string $url
     *
     * @return bool
     * @throws RuntimeException
     */
    public function checkValidUrl(string $url): bool
    {
        $valid = false;
        if (strpos($url, self::YOUTUBE) !== false) {
            if ($this->getYouTubeApiKey()) {
                $valid = true;
            } else {
                $valid = false;
            }
        } elseif (strpos($url, 'vimeo') !== false) {
            $valid = true;
        }
        return $valid;
    }

    /**
     * @param string $url
     *
     * @return string
     */
    public function getVimeoVideoImage(string $url): string
    {
        return $this->getVimeoVideoDetails($url)['thumbnail_large'] ?? '';
    }

    /**
     * @param string $url
     *
     * @return array
     */
    private function getVimeoVideoDetails(string $url): array
    {
        $localFileName = Uploader::getCorrectFileName(basename($url));
        $hash = $this->serialize
            ->unserialize(file_get_contents("http://vimeo.com/api/v2/video/$localFileName.php"));
        return $hash[0];
    }

    /**
     * @param string $url
     *
     * @return string
     */
    public function getYoutubeVideoImage(string $url): string
    {
        return 'https://img.youtube.com/vi/' . $this->getYoutubeVideoId($url) . '/hqdefault.jpg';
    }

    /**
     * @param string $url
     * @return mixed
     */
    private static function parseURL(string $url)
    {
        return parse_url($url);
    }

    /**
     * @param string $url
     *
     * @return array
     */
    public function getVideoDetails(string $url): array
    {
        $videoDetail = [];
        if (strpos($url, self::YOUTUBE) !== false) {
            if ($this->getYouTubeApiKey()) {
                $videoDetail = $this->getYoutubeData($url);
            }
        } elseif (strpos($url, 'vimeo') !== false) {
            $videoDetail = $this->getVimeoVideoDetails($url);
        }
        return $videoDetail;
    }

    /**
     * @param string $url
     *
     * @return array
     */
    private function getYoutubeData(string $url): array
    {
        $videoURL = static::YOUTUBE_URL;
        $youtubeVideoId = $this->getYoutubeVideoId($url);

        $params = [
            'id' => $youtubeVideoId,
            'key' => $this->getYouTubeApiKey(),
            'part' => 'id, snippet',
        ];
        $videoURL = $videoURL . (strpos($videoURL, '?') === false ? '?' : '') . http_build_query($params);
        $this->curl->get($videoURL);
        $videoData = $this->serializer->unserialize($this->curl->getBody());
        $title = $videoData['items'][0]['snippet']['title'] ?? __('Default Title');
        $description = $videoData['items'][0]['snippet']['description'] ?? __('Default Description');
        return [
            'title' => $title,
            'description' => $description,
        ];
    }

    /**
     * @param $youtubeUrl
     * @return mixed|string
     */
    private function getYoutubeVideoId($youtubeUrl)
    {
        preg_match("#(?<=v=|v\/|vi=|vi\/|youtu.be\/)[a-zA-Z0-9_-]{11}#", $youtubeUrl, $match);
        $youtubeVideoId = array_shift($match);
        return $youtubeVideoId ?? '';
    }
}
