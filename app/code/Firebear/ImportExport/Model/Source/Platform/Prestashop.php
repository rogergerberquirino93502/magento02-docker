<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Platform;

use Firebear\ImportExport\Model\Cache\Type\ImportProduct as ImportProductCache;
use Firebear\ImportExport\Model\Import\Product;
use Firebear\ImportExport\Traits\General;
use Magento\Backend\Model\Session;
use Magento\Catalog\Model\Product\Url;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory;
use Magento\Eav\Model\Entity\Context;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\File\ReadFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Model\ClassModelFactory;
use Symfony\Component\Console\Output\ConsoleOutput;
use TMWK\ClientPrestashopApi\PrestaShopWebService;
use Psr\Log\LoggerInterface;

/**
 * Class Prestashop
 *
 * @package Firebear\ImportExport\Model\Source\Platform
 * @author  Firebear Studio <fbeardev@gmail.com>
 */
class Prestashop extends AbstractPlatform
{
    use General;

    protected $parameters;
    protected $prestaShopClient;
    protected $apiURL;
    protected $output;
    protected $separator;

    protected $productOptions = [];
    protected $productOptionValues = [];
    protected $productCombinations = [];
    protected $productCategories = [];

    /**
     * Entity model parameters
     *
     * @var array
     */
    protected $_parameters = [];

    /**
     * @todo instead of language key take from user input in case of multiple languages
     * @var int
     */
    private $languageKey = 1;

    /** @var \Psr\Log\LoggerInterface */
    protected $_logger;

    /** @var \Magento\Catalog\Model\Product\Url  */
    protected $productUrl;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * Prestashop constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Filesystem\File\ReadFactory $readFactory
     * @param \Magento\Framework\File\Csv $csvProcessor
     * @param \Magento\Tax\Model\ClassModelFactory $taxFactory
     * @param \Magento\Catalog\Model\Product\Visibility $visibility
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attributeSetCollectionFactory
     * @param \Firebear\ImportExport\Model\Import\Product $importProduct
     * @param \Magento\Eav\Model\Entity\Context $context
     * @param \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attributeFactory
     * @param \Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory $resourceFactory
     * @param \Magento\Backend\Model\Session $session
     * @param \Symfony\Component\Console\Output\ConsoleOutput $output
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Catalog\Model\Product\Url $productUrl
     * @param CacheInterface $cache
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Filesystem $filesystem,
        ReadFactory $readFactory,
        Csv $csvProcessor,
        ClassModelFactory $taxFactory,
        Visibility $visibility,
        CollectionFactory $attributeSetCollectionFactory,
        Product $importProduct,
        Context $context,
        EavSetupFactory $eavSetupFactory,
        StoreManagerInterface $storeManager,
        Attribute $attributeFactory,
        ResourceModelFactory $resourceFactory,
        Session $session,
        ConsoleOutput $output,
        LoggerInterface $logger,
        Url $productUrl,
        CacheInterface $cache
    ) {
        parent::__construct(
            $scopeConfig,
            $filesystem,
            $readFactory,
            $csvProcessor,
            $taxFactory,
            $visibility,
            $attributeSetCollectionFactory,
            $importProduct,
            $context,
            $eavSetupFactory,
            $storeManager,
            $attributeFactory,
            $resourceFactory,
            $session,
            $cache,
            $output
        );
        $this->output = $output;
        $this->_logger = $logger;
        $this->productUrl = $productUrl;
    }

    /**
     * Processed to prepareColumn Values before import
     *
     * @param array $rowData The rowData to filter things
     *
     * @return mixed
     */
    public function prepareColumns($rowData)
    {
        if (in_array('_root_category', $rowData) || in_array('_category', $rowData)) {
            $key = array_search('_root_category', $rowData);
            if ($key !== false) {
                $rowData[$key] = 'categories';
            }
            $keySecond = array_search('_category', $rowData);

            if ($keySecond !== false) {
                if ($key !== false) {
                    unset($rowData[$keySecond]);
                } else {
                    $rowData[$keySecond] = 'categories';
                }
            }
        }

        return $rowData;
    }

    /**
     * Delete Columns
     *
     * @param array $array
     *
     * @return mixed
     */
    public function deleteColumns($array)
    {
        return $array;
    }

    /**
     * @param array $rowData
     * @param array $maps
     *
     * @return mixed
     */
    public function afterColumns($rowData, $maps)
    {
        if (empty($maps)) {
            return $rowData;
        }
        $systems = [];
        foreach ($maps as $field) {
            $systems[] = $field['system'];
        }
        foreach ($rowData as $key => $item) {
            if (!in_array($item, $systems)) {
                unset($rowData[$key]);
            }
        }

        return $rowData;
    }

    /**
     * @todo improve validation
     * Save Validated Bunches
     *
     * @param $source
     * @param $maxDataSize
     * @param $bunchSize
     * @param $dataSourceModel
     * @param $parameters
     * @param $entityTypeCode
     * @param $behavior
     * @param $processedRowsCount
     * @param $separator
     * @param Product $model
     *
     * @return $this
     */
    public function saveValidatedBunches(
        $source,
        $maxDataSize,
        $bunchSize,
        $dataSourceModel,
        $parameters,
        $entityTypeCode,
        $behavior,
        $processedRowsCount,
        $separator,
        Product $model
    ) {
        if (!class_exists(PrestaShopWebService::class)) {
            $this->addLogWriteln(
                __('Please run composer require tmwk/client-prestashop-api at root'),
                $this->output,
                'error'
            );
            return $this;
        }
        $this->parameters = $parameters;
        $this->fetchPrestashopDetails($parameters);
        $currentDataSize = 0;
        $bunchRows = [];
        $prevData = [];
        $startNewBunch = false;
        $nextRowBackup = [];
        $repeatStore = 0;
        $source->rewind();
        $dataSourceModel->cleanBunches();
        $file = null;
        $jobId = null;

        if (isset($parameters['file'])) {
            $file = $parameters['file'];
        }
        if (isset($parameters['job_id'])) {
            $jobId = $parameters['job_id'];
        }
        $repeats = 0;
        $end = 0;
        while ($source->valid() || $bunchRows) {
            if ($startNewBunch || !$source->valid()) {
                $dataSourceModel->saveBunches(
                    $entityTypeCode,
                    $behavior,
                    $jobId,
                    $file,
                    $bunchRows
                );
                $bunchRows = $nextRowBackup;
                $currentDataSize = strlen($model->getJsonHelper()->jsonEncode($bunchRows));
                $startNewBunch = false;
                $nextRowBackup = [];
            }
            if ($source->valid()) {
                try {
                    $rowData = $source->current();
                    $isCached = $parameters['cache_products'] ?? false;
                    if ($isCached) {
                        $isRowDataInCache = $this->isRowDataInCache($model, $rowData);
                        if ($isRowDataInCache) {
                            $source->next();
                            continue;
                        }
                    }
                } catch (\InvalidArgumentException $e) {
                    $model->addRowError($e->getMessage(), $processedRowsCount);
                    $processedRowsCount++;
                    $source->next();
                    continue;
                }
                if (empty($rowData['sku']) && !$end) {
                    $prevData = $this->mergeData($rowData, $prevData, $separator);
                    $source->next();
                    $repeats++;
                    if ($source->valid()) {
                        continue;
                    } else {
                        $end = 1;
                    }
                }

                $rowData = $model->customFieldsMapping($rowData);
                if (empty($rowData['name'])) {
                    $repeatStore = 1;
                }
                if (!empty($prevData) && $repeats > 0) {
                    $this->separator = $separator;
                    $prevData = $this->prepareRow($prevData);
                    if ($simplesCount > 0 && isset($prevData['config'])) {
                        $configurables['items'][] = 'sku=' . $prevData['sku'] . $prevData['config'];
                    }
                    $processedRowsCount++;
                    $rowSize = strlen($model->getJsonHelper()->jsonEncode($prevData));
                    $isBunchSizeExceeded = $bunchSize > 0 && count($bunchRows) >= $bunchSize;
                    if ($currentDataSize + $rowSize >= $maxDataSize || $isBunchSizeExceeded) {
                        $startNewBunch = true;
                        $nextRowBackup = [$processedRowsCount => $prevData];
                    } else {
                        $bunchRows[$processedRowsCount] = $prevData;
                        $currentDataSize += $rowSize;
                    }
                }
                if ($repeatStore) {
                    $simplesCount++;
                    $prevData = array_merge($prevData, $this->deleteEmpty($rowData));
                    if ($simplesCount == 1) {
                        $configurables = ['data' => $prevData, 'items' => []];
                    }
                    $repeatStore = 0;
                } else {
                    if (!empty($configurables)) {
                        $confData = $configurables['data'];
                        $confData['sku'] .= '-Conf';
                        $confData['product_type'] = 'configurable';
                        $confData['configurable_variations'] = implode("|", $configurables['items']);
                        $processedRowsCount++;
                        $rowSize = strlen($model->getJsonHelper()->jsonEncode($confData));
                        $isBunchSizeExceeded = $bunchSize > 0 && count($bunchRows) >= $bunchSize;
                        if ($currentDataSize + $rowSize >= $maxDataSize || $isBunchSizeExceeded) {
                            $startNewBunch = true;
                            $nextRowBackup = [$processedRowsCount => $prevData];
                        } else {
                            $bunchRows[$processedRowsCount] = $confData;
                            $currentDataSize += $rowSize;
                        }
                    }
                    $simplesCount = 0;
                    $prevData = $rowData;
                }
                if (!$end) {
                    $repeats = 1;
                    $key = $source->key();
                }

                $source->next();
                if (!$source->valid() && $end == 0) {
                    $source->rewind();
                    $source->seek($key);
                    $end = 1;
                }
            }
        }
        $this->setProcessedRowsCount($processedRowsCount);
        if (empty($processedRowsCount)) {
            $errorMessage = __('This file is empty. Please try another one.');
            $model->addLogWriteln($errorMessage, null, 'warning');
        }
        $this->saveRowDataInBuffCache($model);
        return $this;
    }

    /**
     * Fetch All Details from Presta Shop
     *
     * @param array $parameters
     *
     * @since 3.0
     *
     * @return void
     */
    protected function fetchPrestashopDetails($parameters)
    {
        preg_match(
            '/^((?:http:\/\/|https:\/\/)?(?:.+?))(?:\s*$|\/.*$)/',
            $parameters['request_url'],
            $apiURL
        );
        $this->apiURL = $apiURL[1];
        $this->productOptions = $this->getProductOptions();
        $this->productOptionValues = $this->getProductOptionValues();
        $this->productCategories = $this->getProductCategories();
        $this->productCombinations = $this->getProductCombinations();
    }

    /**
     * Get Prestashop Categories
     *
     * @return array
     */
    protected function getProductCategories()
    {
        $categoriesArray = [];
        try {
            $prestashop = $this->getPrestaShopClient();
            $this->addLogWriteln(
                __('Fetching Product Categories'),
                $this->output,
                'info'
            );
            foreach ($prestashop->Categories()->findAll() as $categories) {
                foreach ($categories->xpath('category') as $category) {
                    $category = $this->convertToArray1($category);
                    $categoriesArray[$category['id']] = [
                        'name' => $category['name']['language'][$this->languageKey],
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->addLogWriteln($e->getMessage());
        }
        return $categoriesArray;
    }

    /**
     * Get Product Combinations from Presta Shop
     *
     * @return array
     */
    protected function getProductCombinations()
    {
        $combinations = [];
        try {
            $prestashop = $this->getPrestaShopClient();
            $this->addLogWriteln(
                __('Fetching Product Combinations'),
                $this->output,
                'info'
            );
            foreach ($prestashop->Combinations()->findAll() as $productCombinations) {
                foreach ($productCombinations as $productCombination) {
                    $productCombination = $this->convertToArray1($productCombination);
                    $combinations[$productCombination['id']] = $productCombination;
                }
            }
        } catch (\Exception $e) {
            $this->addLogWriteln($e->getMessage());
        }
        return $combinations;
    }

    /**
     * @return array
     */
    protected function getProductOptions()
    {
        $options = [];
        try {
            $prestashop = $this->getPrestaShopClient();
            $this->addLogWriteln(__('Fetching Product Options from Prestashop'), $this->output, 'info');
            foreach ($prestashop->ProductOptions()->findAll() as $productOptions) {
                foreach ($productOptions->xpath('product_option') as $productOption) {
                    $productOption = $this->convertToArray1($productOption);
                    $options[$productOption['id']] = [
                        'type' => $productOption['group_type'],
                        'name' => $productOption['name']['language'][$this->languageKey],
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->addLogWriteln($e->getMessage());
        }
        return $options;
    }

    /**
     * @return \TMWK\ClientPrestashopApi\PrestaShopWebService
     */
    protected function getPrestaShopClient()
    {
        if (!$this->prestaShopClient) {
            $requestOption = json_decode($this->parameters['request_options'], true);
            $this->prestaShopClient = new PrestaShopWebService($this->apiURL, $requestOption['username'], false);
        }

        return $this->prestaShopClient;
    }

    /**
     * @param $xmlObject
     * @param array $out
     *
     * @return array
     */
    public function convertToArray1($xmlObject, $out = [])
    {
        foreach ((array)$xmlObject as $index => $node) {
            $out[$index] = (is_object($node)) ? $this->convertToArray1($node) : $node;
        }
        return $out;
    }

    /**
     * @return array
     */
    protected function getProductOptionValues()
    {
        $options = [];
        try {
            $prestashop = $this->getPrestaShopClient();
            $this->addLogWriteln(__('Fetching Product Option Values from Prestashop'), $this->output, 'info');
            foreach ($prestashop->ProductOptionValues()->findAll() as $productOptions) {
                foreach ($productOptions->xpath('product_option_value') as $productOption) {
                    $productOption = $this->convertToArray1($productOption);
                    $options[$productOption['id']] = [
                        'code' => $productOption['id_attribute_group'],
                        'name' => $productOption['name']['language'][$this->languageKey],
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->addLogWriteln($e->getMessage());
        }
        return $options;
    }

    /**
     * Prepare row method
     *
     * @param  [] $rowData
     *
     * @return  []
     */
    public function prepareRow($rowData)
    {
        $rowData['url_key'] = $rowData['name'] . '-' . $rowData['sku'];

        if (isset($rowData['excludeImportCategories']) && $rowData['excludeImportCategories'] == "false") {
            if (isset($rowData['categories_link'])) {
                $catName = [];
                foreach (explode($this->separator, $rowData['categories_link']) as $categoryId) {
                    $name = $this->getCategoryName($categoryId);
                    if ($name != '' && isset($rowData['_root_category'])) {
                        $catName[] = $rowData['_root_category'] . '/' . $name;
                    } elseif ($name != '') {
                        $catName[] = $name;
                    } else {
                        $catName[] = $rowData['_root_category'];
                    }
                }
                if (!empty($catName)) {
                    $rowData['categories'] = implode(',', $catName);
                }
            }
        }

        if (isset($rowData['config_product_id']) && $rowData['config_product_id'] > 0) {
            $configData = $this->getCombinationProduct($rowData['config_product_id'], $rowData);
            $rowData = array_merge($rowData, $configData);
        }

        /** @todo improve image fetching assigning logic */
        if (isset($this->parameters['request_options'])) {
            $requestOption = json_decode($this->parameters['request_options'], true);
            if (isset($requestOption['username'])) {
                if (isset($rowData['image'])) {
                    $rowData['image'] = str_replace(
                        'https://',
                        'http://' . $requestOption['username'] . '@',
                        $rowData['image']
                    );
                }
                if (isset($rowData['additional_images'])) {
                    $rowData['additional_images'] = str_replace(
                        'https://',
                        'http://' . $requestOption['username'] . '@',
                        $rowData['additional_images']
                    );
                }
            }
        }
        if (isset($rowData['additional_images']) && $rowData['additional_images'] != '') {
            $images = explode($this->separator, $rowData['additional_images']);
            if (!isset($rowData['image'])) {
                $rowData['image'] = $images[0];
                unset($images[0]);
            }
            $rowData['additional_images'] = implode($this->separator, $images);
        }
        return $rowData;
    }

    /**
     * @param $id
     * @param $rowData
     *
     * @return array
     */
    protected function getCombinationProduct($id, $rowData)
    {
        $configData = [];
        if ($id === '') {
            return $configData;
        }
        try {
            if (isset($this->productCombinations[$id])) {
                $combination = $this->convertToArray1($this->productCombinations[$id]);
                if (\is_array($combination)) {
                    $configData['sku'] = $combination['reference'] ?? $combination['id'];
                    $configData['url_key'] = $this->productUrl
                        ->formatUrlKey($combination['reference'] . '-' . $rowData['name']);
                    if (isset($combination['quantity']) && $combination['quantity'] > 0) {
                        $configData['qty'] = $combination['quantity'];
                    }
                    $configData['ean13'] = $combination['ean13'] ?? '';
                    if (isset($combination['price']) && $combination['price'] > 0) {
                        $configData['price'] = $combination['price'];
                    }
                    if (isset($combination['associations']['product_option_values']['product_option_value'])) {
                        $productOptionValues = $combination['associations']['product_option_values'];
                        foreach ($productOptionValues['product_option_value'] as $product_option_value) {
                            $product_option_value = $this->convertToArray1($product_option_value);
                            if (isset($product_option_value['id'])) {
                                if (isset($this->productOptionValues[$product_option_value['id']])) {
                                    $productOptionValue = $this->productOptionValues[$product_option_value['id']];
                                    $attrCode = $this->productOptions[$productOptionValue['code']];
                                    if ($attrCode['type'] == 'select') {
                                        $configData[strtolower($attrCode['name'])] = $productOptionValue['name'];
                                    }
                                }
                            }
                        }
                    }
                    $images = [];
                    if (isset($combination['associations']['images']['image'])) {
                        $endPoint = 'api/' . $combination['associations']['images']['@attributes']['api'];
                        foreach ($combination['associations']['images']['image'] as $image) {
                            $image = $this->convertToArray1($image);
                            if (isset($image['id'])) {
                                $images[] = $this->apiURL . '/' . $endPoint . '/' .
                                    $combination['id_product'] . '/' . $image['id'];
                            }
                        }
                    }
                    if (!empty($images)) {
                        $configData['additional_images'] = \implode($this->separator, $images);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->addLogWriteln($e->getMessage(), $this->output, 'error');
        }
        return $configData;
    }

    /**
     * @param $id
     *
     * @return string
     */
    protected function getCategoryName($id)
    {
        $catName = '';
        if ($id == '') {
            return $catName;
        }
        try {
            if (isset($this->productCategories[$id])) {
                $catName = $this->productCategories[$id]['name'];
            }
        } catch (\Exception $e) {
            $this->addLogWriteln($e->getMessage());
        }
        return $catName;
    }

    /**
     * @param $array
     *
     * @return array
     */
    protected function deleteEmpty($array)
    {
        if (isset($array['sku'])) {
            unset($array['sku']);
        }
        $newElement = [];
        foreach ($array as $key => $element) {
            if (is_object($element) || is_array($element)) {
                continue;
            }
            if (strlen($element)) {
                $newElement[$key] = $element;
            }
        }

        return $newElement;
    }
}
