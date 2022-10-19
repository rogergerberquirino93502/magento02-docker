<?php
declare(strict_types=1);
/**
 * UrlKeyManagerInterface
 *
 * @copyright Copyright © 2018 Firebear Studio. All rights reserved.
 * @author    Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Api;

use Exception;
use Firebear\ImportExport\Model\Import\Product;
use Magento\Store\Model\Store;

/**
 * Interface UrlKeyManagerInterface
 * @package Firebear\ImportExport\Api
 * @api
 * @since 3.1.4
 */
interface UrlKeyManagerInterface
{
    /**
     * Initialize all product url_keys for the SKU so there is no override unless mentioned in url_key
     * and helps in generation of url_key
     *
     * @return $this
     * @throws Exception
     */
    public function initUrlKeys();

    /**
     * @param $sku
     * @param $urlKey
     *
     * @return $this
     */
    public function addUrlKeys($sku, $urlKey, $storeId = Store::DEFAULT_STORE_ID);

    /**
     * @return array
     */
    public function getUrlKeys();

    /**
     * @param $sku
     * @param $urlKey
     * @param $storeId
     *
     * @return bool
     */
    public function isUrlKeyExist($sku, $urlKey, $storeId);

    /**
     * @param Product $entity
     * @return $this
     */
    public function setEntity(Product $entity);
}
