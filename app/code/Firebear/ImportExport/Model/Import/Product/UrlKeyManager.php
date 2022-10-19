<?php
declare(strict_types=1);
/**
 * UrlKeyManager
 *
 * @copyright Copyright © 2018 Firebear Studio. All rights reserved.
 * @author    Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Product;

use Exception;
use Firebear\ImportExport\Api\UrlKeyManagerInterface;
use Firebear\ImportExport\Model\Import\Product;
use Magento\Catalog\Model\Product\Url;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\Store;

/**
 * Class UrlKeyManager
 * @package Firebear\ImportExport\Model\Import\Product
 * @api
 * @since 3.1.4
 */
class UrlKeyManager implements UrlKeyManagerInterface
{
    /**
     * [urlKey] => SKU
     * @var array
     */
    protected $importUrlKeys = [];

    /**
     * [SKU] => [urlKey]
     * @var array
     */
    protected $importSkuUrlKeys = [];

    /**
     * @var Product
     */
    protected $entity;

    /**
     * @var Url
     */
    protected $productUrl;

    /**
     * @var resource
     */
    protected $_resource;

    /**
     * UrlKeyManager constructor.
     * @param Url $productUrl
     * @param ResourceConnection $resource
     */
    public function __construct(
        Url $productUrl,
        ResourceConnection $resource
    ) {
        $this->productUrl = $productUrl;
        $this->_resource = $resource;
    }

    /**
     * Initialize all product url_keys for the SKU so there is no override unless mentioned in url_key
     * and helps in generation of url_key
     *
     * @return $this
     * @throws Exception
     */
    public function initUrlKeys()
    {
        try {
            $entityLinkField = $this->getEntity()->_getProductEntityLinkField();
            $joinCondition = 'cpe.' . $entityLinkField . ' = attr.' . $entityLinkField;
            $urlKeyAttribute = $this->getEntity()
                ->retrieveAttributeByCode(Product::URL_KEY);
            $connection = $this->getEntity()->getConnection();

            $select = $connection->select()
                ->from(['attr' => $urlKeyAttribute->getBackendTable()], ['url_key' => 'value', 'store_id'])
                ->joinLeft(
                    ['cpe' => $this->_resource->getTableName('catalog_product_entity')],
                    $joinCondition,
                    ['sku']
                )
                ->where('attribute_id = (?)', $urlKeyAttribute->getAttributeId());

            foreach ($connection->fetchAll($select) as $value) {
                if (!isset($value[Product::COL_SKU])) {
                    continue;
                }
                $this->addUrlKeys($value[Product::COL_SKU], $value[Product::URL_KEY], $value['store_id']);
            }
        } catch (Exception $exception) {
            $this->addLogWriteln($exception->getMessage(), 'error');
        }
        return $this;
    }

    /**
     * @param $message
     * @param string $type
     * @return $this
     */
    private function addLogWriteln($message, $type = 'info')
    {
        if ($this->getEntity() instanceof Product
            && method_exists($this->getEntity(), 'addLogWriteln')
            && method_exists($this->getEntity(), 'getOutput')
        ) {
            $this->getEntity()
                ->addLogWriteln(
                    $message,
                    $this->getEntity()->getOutput(),
                    $type
                );
        }
        return $this;
    }

    /**
     * @return Product
     */
    protected function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param Product $entity
     * @return $this
     */
    public function setEntity(Product $entity)
    {
        $this->entity = $entity;
        return $this;
    }

    /**
     * @param $sku
     * @param $urlKey
     * @param $storeId
     *
     * @return $this
     */
    public function addUrlKeys($sku, $urlKey, $storeId = Store::DEFAULT_STORE_ID)
    {
        $sku = $this->formatSku($sku);
        if (!isset($this->importUrlKeys[$storeId][$urlKey])) {
            $this->importUrlKeys[$storeId][$urlKey] = $sku;
        }
        if (!isset($this->importSkuUrlKeys[$storeId][$sku])) {
            $this->importSkuUrlKeys[$storeId][$sku] = $urlKey;
        } else {
            $this->updateUrlKeys($sku, $urlKey, $storeId);
        }
        return $this;
    }

    /**
     * @param $sku
     * @param $urlKey
     * @param $storeId
     *
     * @return $this
     */
    protected function updateUrlKeys($sku, $urlKey, $storeId = Store::DEFAULT_STORE_ID)
    {
        $parameters = $this->entity->getParameters();
        if (isset($parameters['enable_product_url_pattern']) && $parameters['enable_product_url_pattern'] !== 0) {
            // search old sku for new url
            $oldSku = array_search($urlKey, $this->importSkuUrlKeys[$storeId]);
            if (false !== $oldSku && $oldSku != $sku) {
                return $this;
            }
            // search old url for sku
            $oldUrlKey = array_search($sku, $this->importUrlKeys[$storeId]);
            if (false !== $oldUrlKey && $oldUrlKey != $urlKey) {
                $this->importUrlKeys[$storeId][$urlKey] = $this->importUrlKeys[$storeId][$oldUrlKey];
                unset($this->importUrlKeys[$storeId][$oldUrlKey]);
            }
            $this->importSkuUrlKeys[$storeId][$sku] = $urlKey;
        }
        return $this;
    }

    /**
     * @param string $sku
     * @return false|string|string[]|null
     */
    private function formatSku($sku)
    {
        return mb_strtolower($sku);
    }

    /**
     * @return array
     */
    public function getUrlKeys()
    {
        return $this->importUrlKeys;
    }

    /**
     * @param $urlKey
     * @param $sku
     * @param int $i
     * @param $storeId
     * @return mixed
     */
    public function generateUrlKey($urlKey, $sku, int $i = 0, $storeId = Store::DEFAULT_STORE_ID)
    {
        if ($this->isUrlKeyExist($sku, $urlKey, $storeId)) {
            $urlKey = $this->productUrl->formatUrlKey($urlKey . '-' . $sku);
            $this->generateUrlKey($urlKey, $sku, ++$i);
        }
        return $urlKey;
    }

    /**
     * @param $sku
     * @param $urlKey
     * @param $storeId
     *
     * @return bool|mixed
     */
    public function isUrlKeyExist($sku, $urlKey, $storeId = Store::DEFAULT_STORE_ID)
    {
        $sku = mb_strtolower($sku);
        if (isset($this->importUrlKeys[$storeId][$urlKey]) && $this->importUrlKeys[$storeId][$urlKey] !== $sku) {
            return true;
        }
        return false;
    }

    /**
     * @param $sku
     * @param $storeId
     * @return mixed|null
     */
    public function getUrlKeyForSku($sku, $storeId = Store::DEFAULT_STORE_ID)
    {
        return $this->importSkuUrlKeys[$storeId][$sku] ?? null;
    }
}
