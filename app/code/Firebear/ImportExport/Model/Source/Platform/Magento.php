<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Platform;

use Firebear\ImportExport\Model\Cache\Type\ImportProduct as ImportProductCache;
use Magento\Catalog\Model\Product\Visibility;
use Firebear\ImportExport\Model\Import\Product;
use Magento\Backend\Model\Session;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory;
use Magento\Eav\Model\Entity\Context;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\File\ReadFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Model\ClassModelFactory;
use Magento\Bundle\Model\Product\Price as BundlePrice;
use Magento\BundleImportExport\Model\Import\Product\Type\Bundle;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class Magento
 *
 * @package Firebear\ImportExport\Model\Source\Platform
 */
class Magento extends AbstractPlatform
{
    const PRICE_FIXED = 0;

    protected $separator;

    /**
     * @var \Magento\Framework\App\ProductMetadata
     */
    protected $productMetadata;

    /**
     * Magento constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param Filesystem $filesystem
     * @param ReadFactory $readFactory
     * @param Csv $csvProcessor
     * @param ClassModelFactory $taxFactory
     * @param Visibility $visibility
     * @param CollectionFactory $attributeSetCollectionFactory
     * @param Product $importProduct
     * @param Context $context
     * @param EavSetupFactory $eavSetupFactory
     * @param StoreManagerInterface $storeManager
     * @param Attribute $attributeFactory
     * @param ResourceModelFactory $resourceFactory
     * @param Session $session
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
        CacheInterface $cache,
        ConsoleOutput $output
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

        $this->unsetColumns = [
            '_group_price_website',
            '_group_price_customer_group',
            '_group_price_price'
        ];
    }

    /**
     * Prepare Rows
     *
     * @param $rowData
     *
     * @return mixed
     */
    public function prepareRow($rowData)
    {
        /*tax phase*/
        if (isset($rowData['tax_class_id'])) {
            $rowData['tax_class_name'] = $this->getTaxClassName($rowData['tax_class_id']);
            unset($rowData['tax_class_id']);
        }

        /*visibility phase*/
        if (isset($rowData['visibility'])) {
            $rowData['visibility'] = $this->getVisibilityText($rowData['visibility']);
        }

        $attrList = ['gift_message_available', 'shipment_type', 'page_layout'];
        foreach ($attrList as $item) {
            if (isset($rowData[$item])) {
                $rowData[$item] = $this->getAttrValues($item, $rowData[$item]);
            }
        }

        if ($rowData['product_type'] == 'bundle') {
            $fields = ['price_type', 'weight_type', 'sku_type'];
            foreach ($fields as $field) {
                if (isset($rowData[$field])) {
                    if ($rowData[$field] == BundlePrice::PRICE_TYPE_DYNAMIC) {
                        $rowData[$field] = Bundle::VALUE_DYNAMIC;
                    } else {
                        $rowData[$field] = Bundle::VALUE_FIXED;
                    }
                }
            }
        }

        if ($rowData['product_type'] == 'configurable') {
            if (!empty($rowData['_super_products_sku']) &&
                !empty($rowData['_super_attribute_code']) &&
                !empty($rowData['_super_attribute_option'])
            ) {
                $newArray = [];
                $configuration = [];
                if (!empty($rowData['configurable_variations'])) {
                    $configuration = explode('|', $rowData['configurable_variations']);
                }

                $superSku = explode($this->separator, $rowData['_super_products_sku']);
                $superAttr = explode($this->separator, $rowData['_super_attribute_code']);
                $superOption = explode($this->separator, $rowData['_super_attribute_option']);

                if (count($superSku) == 1) {
                    foreach ($superSku as $skuSuper) {
                        if (count($superAttr) == count($superOption)) {
                            foreach ($superAttr as $key => $attribute) {
                                $newArray[$skuSuper][] = $superAttr[$key] . "=" . $superOption[$key];
                            }
                        }
                    }
                } elseif (count($superSku) > 1) {
                    foreach ($superSku as $key => $skuSuper) {
                        $newArray[$skuSuper][] = $superAttr[$key] . "=" . $superOption[$key];
                    }
                }

                $configurationPart = '';
                foreach ($newArray as $key => $array) {
                    $configurationPart .= 'sku=' . $key;
                    foreach ($array as $arrayItem) {
                        $configurationPart .= $this->separator . $arrayItem;
                    }
                    $configuration[] = $configurationPart;
                }

                if (!empty($configuration)) {
                    $rowData['configurable_variations'] = implode('|', $configuration);
                    unset($rowData['_super_products_sku']);
                    unset($rowData['_super_attribute_code']);
                    unset($rowData['_super_attribute_option']);
                }
            }
        }

        $rowData = $this->changeTierPrices($rowData);
        if (isset($rowData['_attribute_set'])) {
            $rowData['_attribute_set'] = $this->getAttributeSetName($rowData['_attribute_set']);
        }
        if (isset($rowData['_root_category'])) {
            if (isset($rowData['categories'])) {
                $rowData['categories'] = $rowData['_root_category'] . "/" . $rowData['categories'];
            } else {
                $rowData['categories'] = $rowData['_root_category'];
            }
        }

        if (isset($rowData['price']) && !$rowData['price']) {
            $rowData['price'] = 0;
        }

        /*bundle phase*/
        if (isset($rowData['bundle_configurations']) && isset($rowData['bundle_values'])) {
            if ($rowData['bundle_configurations']) {
                $bundleConfigurations = explode(',', $rowData['bundle_configurations']);
                foreach ($bundleConfigurations as $bundleConfigData) {
                    $bundleConfigData = explode('=', $bundleConfigData);
                    $rowData[$bundleConfigData[0]] = $bundleConfigData[1];
                }
            } else {
                $rowData['bundle_price_type'] = '';
                $rowData['bundle_sku_type'] = '';
                $rowData['bundle_price_view'] = '';
                $rowData['bundle_weight_type'] = '';
            }
        }

        $rowData = $this->unsetColumns($rowData, $this->unsetColumns);
        $rowData['custom_options'] = $this->formatCustomOptions($rowData);
        $array = [
            '_custom_option_title',
            '_custom_option_type',
            '_custom_option_is_required',
            '_custom_option_sku',
            '_custom_option_price',
            '_custom_option_row_title',
            '_custom_option_store',
            '_custom_option_max_characters',
            '_custom_option_sort_order',
            '_custom_option_row_price',
            '_custom_option_row_sku',
            '_custom_option_row_sort'
        ];

        foreach ($array as $field) {
            if (isset($rowData[$field])) {
                unset($rowData[$field]);
            }
        }
        if (isset($rowData['_store'])) {
            $rowData['store_view_code'] = $rowData['_store'];
        } else {
            $rowData['store_view_code'] = '';
        }

        return $rowData;
    }

    /**
     * @param $rowData
     * @return mixed
     */
    public function prepareColumns($rowData)
    {
        /*tax phase*/
        foreach ($this->unsetColumns as $field) {
            if (in_array($field, $rowData)) {
                $key = array_search($field, $rowData);
                unset($rowData[$key]);
            }
        }
        if (in_array('tax_class_id', $rowData)) {
            $key = array_search('tax_class_id', $rowData);
            $rowData[$key] = 'tax_class_name';
        }

        if (in_array('reward_update_notification', $rowData)) {
            $key = array_search('reward_update_notification', $rowData);
            unset($rowData[$key]);
        }
        if (in_array('reward_warning_notification', $rowData)) {
            $key = array_search('reward_warning_notification', $rowData);
            unset($rowData[$key]);
        }

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
     * Unset unnecessary columns
     *
     * @param $data
     * @param $columnNames
     *
     * @return mixed
     */
    public function unsetColumns($data, $columnNames)
    {
        foreach ($columnNames as $column) {
            unset($data[$column]);
        }

        return $data;
    }

    /**
     * Get tax class name
     *
     * @param $taxId
     *
     * @return mixed|string
     */
    public function getTaxClassName($taxId)
    {
        if (!is_numeric($taxId)) {
            return '';
        }
        $taxInfo = $this->taxFactory->create()->load($taxId);

        if ($taxInfo->getClassName()) {
            return $taxInfo->getClassName();
        }

        return '';
    }

    /**
     * Get tax visibility label
     *
     * @param $visibilityId
     *
     * @return string
     */
    public function getVisibilityText($visibilityId)
    {
        if (!$visibilityId) {
            return '';
        }
        $optionText = $this->visibility->getOptionText($visibilityId);

        return $optionText
            ? (string)$optionText
            : (string)$this->visibility->getOptionText(
                Visibility::VISIBILITY_NOT_VISIBLE
            );
    }

    public function getAttrValues($name, $value)
    {
        if (!strlen($value)) {
            return '';
        }

        $newValue = '';
        $collection = $this->attributeFactory->getCollection()->addFieldToFilter('attribute_code', $name);
        if ($collection->getSize()) {
            $item = $collection->getFirstItem();
            if ($item->getFrontendInput() == 'boolean') {
                if ($value == 0) {
                    $newValue = __('No');
                } else {
                    $newValue = __('Yes');
                }
            } else {
                foreach ($item->getOptions() as $option) {
                    $optValue = $option->getValue();
                    if ($optValue instanceof \Magento\Framework\Phrase) {
                        $optValue = $optValue->__toString();
                    };
                    if ($value instanceof \Magento\Framework\Phrase) {
                        $value = $value->__toString();
                    };
                    if ($optValue == $value) {
                        $newValue = $option->getLabel();
                    }
                }
            }
        }

        return $newValue;
    }

    /**
     * Get Attribute Set Name
     *
     * @param $setName
     *
     * @return mixed
     */
    public function getAttributeSetName($setName)
    {
        $attributeSetCollection = $this->attributeSetCollectionFactory->create();
        $attributeSetName =
            $attributeSetCollection->addFieldToFilter('attribute_set_name', $setName)->getFirstItem();
        if ($attributeSetName->getId()) {
            return $setName;
        }

        return $this->attributeSetCollectionFactory->create()->getFirstItem()->getAttributeSetName();
    }

    public function formatCustomOptions($data)
    {

        $str = "";
        if (isset($data['_custom_option_title']) && $data['_custom_option_title']) {
            $titles = $this->divideData($data['_custom_option_title']);
            $requirds = $this->divideData($data['_custom_option_is_required']);
            foreach ($requirds as $key => $requird) {
                if (!strlen($requird)) {
                    $requirds[$key] = 0;
                }
            }
            $types = $this->divideData($data['_custom_option_type']);
            $skus = $this->divideData($data['_custom_option_sku']);

            $prices = $this->divideData($data['_custom_option_price']);
            $rowTitles = array_unique($this->divideData($data['_custom_option_row_title']));
            for ($i = 0; $i <= count($prices); $i++) {
                if (!isset($skus[$i]) || (isset($skus[$i]) && empty($skus[$i]))) {
                    $skus[$i] = $data['sku'];
                }
            }
            foreach ($prices as $key => $price) {
                if (empty($price) || $price == 0) {
                    if (count($prices) > 1) {
                        array_splice($prices, $key, 1);
                    } else {
                        unset($prices[$key]);
                    }
                    if (isset($titles[$key])) {
                        if (count($titles) > 1) {
                            array_splice($titles, $key, 1);
                        } else {
                            unset($titles[$key]);
                        }
                    }
                    if (isset($requirds[$key])) {
                        if (count($requirds) > 1) {
                            array_splice($requirds, $key, 1);
                        } else {
                            unset($requirds[$key]);
                        }
                    }
                    if (isset($types[$key])) {
                        if (count($types) > 1) {
                            array_splice($types, $key, 1);
                        } else {
                            unset($types[$key]);
                        }
                    }
                    if (isset($skus[$key])) {
                        if (count($skus) > 1) {
                            array_splice($skus, $key, 1);
                        } else {
                            unset($skus[$key]);
                        }
                    }
                    if (isset($rowTitles[$key])) {
                        if (count($rowTitles) > 1) {
                            array_splice($rowTitles, $key, 1);
                        } else {
                            unset($rowTitles[$key]);
                        }
                    }
                }
            }
            if (count($prices) > 0) {
                $bigData = [
                    ['name' => 'name', 'array' => $titles, 'count' => count($titles)],
                    ['name' => 'type', 'array' => $types, 'count' => count($types)],
                    ['name' => 'required', 'array' => $requirds, 'count' => count($requirds)],
                    ['name' => 'sku', 'array' => $skus, 'count' => count($skus)],
                    ['name' => 'price', 'array' => $prices, 'count' => count($prices)],
                    ['name' => 'option_title', 'array' => $rowTitles, 'count' => count($rowTitles)]
                ];

                list($bigData, $max) = $this->getMax($bigData);
                for ($i = 0; $i < $max; $i++) {
                    $text = 0;
                    foreach ($bigData as $field) {
                        if (strlen($field['array'][$i])) {
                            $text++;
                            if ($field['name'] != 'name') {
                                $str .= ",";
                            }
                            $str .= $field['name'] . "=" . $field['array'][$i];
                            if ($field['name'] == 'price') {
                                $str .= ",price_type=fixed";
                            }
                        }
                    }

                    if ($i < $max - 1 && $text) {
                        $str .= "|";
                    }
                }
            }
        }
        return $str;
    }

    /**
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
                    $invalidAttr = [];
                    foreach ($rowData as $attrName => $element) {
                        if (!mb_check_encoding($element, 'UTF-8')) {
                            unset($rowData[$attrName]);
                            $invalidAttr[] = $attrName;
                        }
                    }
                    if (!empty($invalidAttr)) {
                        $model->addRowError(
                            AbstractEntity::ERROR_CODE_ILLEGAL_CHARACTERS,
                            $processedRowsCount,
                            \implode(',', $invalidAttr)
                        );
                    }
                } catch (\InvalidArgumentException $e) {
                    $model->addRowError($e->getMessage(), $processedRowsCount);
                    $processedRowsCount++;
                    $source->next();
                    continue;
                }
                if (empty($rowData['sku'])
                    || (isset($prevData['sku']) && $prevData['sku'] == $rowData['sku']) && !$end) {
                    if (!empty($rowData['_store']) && $rowData['_store'] != $prevData['_store']) {
                        $repeatStore = 1;
                        $repeats++;
                    } else {
                        $prevData = $this->mergeData($rowData, $prevData, $separator);
                        $source->next();
                        $repeats++;
                        if ($source->valid()) {
                            continue;
                        } else {
                            $end = 1;
                        }
                    }
                }

                $rowData = $model->customFieldsMapping($rowData);

                if (!empty($prevData) && $repeats > 0) {
                    if ($model->isExist($prevData['sku'])) {
                        continue;
                    }
                    $this->separator = $separator;
                    $prevData = $this->prepareRow($prevData);
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
                    $prevData = array_merge($prevData, $this->deleteEmpty($rowData));
                    $repeatStore = 0;
                } else {
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

    protected function getMax($array)
    {
        $max = 0;
        foreach ($array as $field) {
            if ($field['count'] > $max) {
                $max = $field['count'];
            }
        }
        foreach ($array as $key => $field) {
            $counts = count($field['array']);
            if ($counts < $max) {
                $diff = $max - $counts;
                $end = end($field['array']);

                for ($i = $counts - 1; $i <= $diff; $i++) {
                    $array[$key]['array'][$i] = $end;
                }
            }
        }

        return [$array, $max];
    }

    /**
     * @param $str
     * @return array
     */
    protected function divideData($str)
    {
        $array = [];
        if (strpos($str, $this->separator) !== false) {
            $array = explode($this->separator, $str);
        } else {
            $array = [$str];
        }

        return $array;
    }

    /**
     * @param $rowData
     * @param $maps
     * @return mixed
     */
    public function afterColumns($rowData, $maps)
    {
        return $rowData;
    }

    protected function changeTierPrices($rowData)
    {
        if (!empty($rowData['_tier_price_price'])) {
            $website = explode($this->separator, $rowData["_tier_price_website"]);
            $group = explode($this->separator, $rowData["_tier_price_customer_group"]);
            $qty = explode($this->separator, $rowData["_tier_price_qty"]);
            $price = explode($this->separator, $rowData["_tier_price_price"]);
            $txt = '';
            foreach ($price as $key => $tier) {
                if (strpos($this->importProduct->productMetadata->getVersion(), '2.2') !== false) {
                    $txt .= $group[$key] . $this->separator . $qty[$key] . $this->separator . $tier .
                        $this->separator . '' . $this->separator . $website[$key];
                } else {
                    $txt .= $group[$key] . $this->separator . $qty[$key]. $this->separator . $tier  .
                        $this->separator . self::PRICE_FIXED .$this->separator .$website[$key];
                }
                if (next($price)) {
                    $txt .= "|";
                }
            }

            $rowData['tier_prices'] = $txt;
            unset($rowData["_tier_price_website"]);
            unset($rowData["_tier_price_customer_group"]);
            unset($rowData['_tier_price_qty']);
            unset($rowData['_tier_price_price']);
        }

        return $rowData;
    }
}
