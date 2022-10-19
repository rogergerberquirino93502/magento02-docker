<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Platform;

use Firebear\ImportExport\Model\Cache\Type\ImportProduct as ImportProductCache;
use Firebear\ImportExport\Model\Import\Product;
use Magento\Backend\Model\Session;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory;
use Magento\Eav\Model\Entity\Context;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\DataObject;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\File\ReadFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Model\ClassModelFactory;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Abstract class for import source types
 * @method \Magento\ImportExport\Model\Import\AbstractSource getSource(array $data)
 *
 * @package Firebear\ImportExport\Model\Source\Platform
 */
abstract class AbstractPlatform extends DataObject implements PlatformInterface
{

    /**
     * CSV Processor
     *
     * @var Csv
     */
    protected $csvProcessor;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $directory;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var ReadFactory
     */
    protected $readFactory;

    /**
     * @var ClassModelFactory
     */
    protected $taxFactory;

    /**
     * @var Visibility
     */
    protected $visibility;

    /**
     * @var CollectionFactory
     */
    protected $attributeSetCollectionFactory;

    /**
     * @var Product
     */
    protected $importProduct;

    /**
     * @var Config
     */
    protected $eavConfig;

    /**
     * @var EavSetupFactory
     */
    protected $eavSetupFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Attribute
     */
    protected $attributeFactory;

    /**
     * @var ResourceModelFactory
     */
    protected $resourceFactory;

    /**
     * DB connection.
     *
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var int
     */
    protected $processedRowsCount;

    /**
     * @var array
     */
    protected $unsetColumns;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var array
     */
    protected $originalImportRows = [];

    /**
     * @var ConsoleOutput
     */
    protected $output;

    /**
     * AbstractPlatform constructor.
     *
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
     * @param ConsoleOutput $output
     * @param array $data
     * @throws \Magento\Framework\Exception\FileSystemException
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
        ConsoleOutput $output,
        array $data = []
    ) {
        $this->scopeConfig                   = $scopeConfig;
        $this->filesystem                    = $filesystem;
        $this->directory                     = $filesystem->getDirectoryWrite(DirectoryList::ROOT);
        $this->readFactory                   = $readFactory;
        $this->csvProcessor                  = $csvProcessor;
        $this->taxFactory                    = $taxFactory;
        $this->visibility                    = $visibility;
        $this->attributeSetCollectionFactory = $attributeSetCollectionFactory;
        $this->importProduct                 = $importProduct;
        $this->eavConfig                     = $context->getEavConfig();
        $this->eavSetupFactory               = $eavSetupFactory;
        $this->storeManager                  = $storeManager;
        $this->attributeFactory              = $attributeFactory;
        $this->resourceFactory               = $resourceFactory;
        $this->connection                    = $context->getResource()->getConnection();
        $this->session                       = $session;
        $this->unsetColumns                  = [
            '_root_category',
            '_category',
            '_type',
            '_links_related_sku',
            '_links_crosssell_sku',
            '_links_upsell_sku',
            '_links_related_position',
            '_links_crosssell_position',
            '_links_upsell_position',
            '_group_price_website',
            '_group_price_customer_group',
            '_group_price_price',
            '_media_lable',
            'tax_class_id',
            '_store',
            '_super_attribute_price_corr',
            '_super_products_sku',
            '_super_attribute_code',
            '_super_attribute_option',
            '_custom_option_store',
            '_custom_option_type',
            '_custom_option_title',
            '_custom_option_is_required',
            '_custom_option_price',
            '_custom_option_sku',
            '_custom_option_max_characters',
            '_custom_option_sort_order',
            '_custom_option_row_title',
            '_custom_option_row_price',
            '_custom_option_row_sku',
            '_custom_option_row_sort',
            '_associated_sku',
            '_associated_default_qty',
            '_associated_position',
            'bundle_configurations'
        ];
        $this->cache = $cache;
        $this->output = $output;

        parent::__construct(
            $data
        );
    }

    /**
     * Get special attributes
     *
     * @return string[]
     */
    public function getSpecialAttributes()
    {
        return $this->importProduct->getSpecialAttributes();
    }

    /**
     * Prepare row method
     *
     * @param  [] $rowData
     *
     * @return []
     */
    abstract public function prepareRow($rowData);

    abstract public function prepareColumns($rowData);

    abstract public function afterColumns($rowData, $maps);

    public function formatCustomOptions($rowData)
    {
        return $rowData;
    }

    public function deleteColumns($array)
    {
        return $this->unsetColumns($array, $this->unsetColumns);
    }

    protected function deleteEmpty($array)
    {
        if (isset($array['sku'])) {
            unset($array['sku']);
        }
        $newElement = [];
        foreach ($array as $key => $element) {
            if (strlen($element)) {
                $newElement[$key] = $element;
            }
        }

        return $newElement;
    }

    protected function mergeData($rowData, $prevData, $separator)
    {

        $data = $this->deleteEmpty($rowData);
        foreach ($data as $key => $value) {
            if (!empty($prevData[$key])) {
                $prevData[$key] .= $separator . $value;
            }
        }

        return $prevData;
    }

    /**
     * @param $processedRowsCount
     */
    public function setProcessedRowsCount($processedRowsCount)
    {
        $this->processedRowsCount = (int)$processedRowsCount;
    }

    /**
     * @return int
     */
    public function getProcessedRowsCount()
    {
        return $this->processedRowsCount;
    }

    /**
     * Check if platform is gateway
     *
     * @return bool
     */
    public function isGateway()
    {
        return false;
    }

    /**
     * Checking for RowData in the cache
     *
     * @param Product $model
     * @param $rowData
     * @return bool
     */
    protected function isRowDataInCache(Product $model, $rowData)
    {
        $currentRowHash = sha1(trim(implode('', $rowData)));
        $cache = $this->cache->load($currentRowHash);
        if ($cache) {
            $model->addLogWriteln(
                __('Product %1 has not changed', $rowData['sku']),
                $this->output,
                'info'
            );
            return true;
        } else {
            $this->originalImportRows[strtolower($rowData['sku'])][] = $currentRowHash;
            return false;
        }
    }

    /**
     * Saving Row Data in the cache
     *
     * @param Product $model
     */
    protected function saveRowDataInBuffCache(Product $model)
    {
        $this->cache->clean([ImportProductCache::BUFF_CACHE]);
        $this->cache->save(
            $model->getSerializer()->serialize($this->originalImportRows),
            sha1(ImportProductCache::BUFF_CACHE)
        );
    }
}
