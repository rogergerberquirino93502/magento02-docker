<?php

namespace Firebear\ImportExport\Model\Import\Product;

use Magento\Store\Model\StoreManagerInterface;
use Firebear\ImportExport\Model\QueueMessage\ImagePublisher;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class Image
 * @package Firebear\ImportExport\Model\Import\Product
 */
class Image
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var array
     */
    protected $headRow = [
        '_media_image',
        'image',
        'small_image',
        'thumbnail',
        '_media_is_disabled',
        'sku',
    ];

    /**
     * @var ImagePublisher
     */
    protected $imagePublisher;

    /**
     * @var array
     */
    protected $rows = [];

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * Image constructor.
     * @param StoreManagerInterface $storeManager
     * @param SerializerInterface $serializer
     * @param ImagePublisher $imagePublisher
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        SerializerInterface $serializer,
        ImagePublisher $imagePublisher
    ) {
        $this->storeManager = $storeManager;
        $this->serializer = $serializer;
        $this->imagePublisher = $imagePublisher;
    }

    /**
     * @param $rowData
     */
    public function addMediaGalleryRows($rowData)
    {
        $row = [];
        foreach ($this->headRow as $name) {
            if (isset($rowData[$name])) {
                $row[$name] = $rowData[$name];
            }
        }

        $this->rows[] = $row;
    }

    /**
     * @param $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function publishBranch()
    {
        $this->imagePublisher->publish($this->serializer->serialize([
            'config' => $this->config,
            'data' => $this->rows
        ]));
        $this->rows = [];
    }

    /**
     * @return array
     */
    protected function getStoreIds()
    {
        $storeIds = array_merge(
            array_keys($this->storeManager->getStores()),
            [0]
        );
        return $storeIds;
    }
}
