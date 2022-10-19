<?php

namespace Firebear\ImportExport\Model\Import\Product;

use Firebear\ImportExport\Model\Import\Product as ImportProduct;
use Magento\CatalogImportExport\Model\Import\Product\StoreResolver;
use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\ResourceModel\Product\Website\Link as ProductWebsiteLink;
use Magento\Catalog\Model\ResourceModel\Product\Action as ProductAction;
use Magento\Store\Model\Store;
use Magento\ImportExport\Model\ResourceModel\Helper as ResourceHelper;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as TypeConfigurable;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Catalog\Api\Data\ProductInterface;

/**
 * Class ConfigurationVariations
 * @package Firebear\ImportExport\Model\Import\Product
 */
class ConfigurationVariations
{
    const DEFAULT_WEBSITE_ID = 1;
    const FIELD_COPY_VALUE = 'copy_simple_value';
    const FIELD_CONF_IMPORT = 'configurable_import_param';

    /**
     * @var array
     */
    protected $products = [];

    /**
     * @var StoreResolver
     */
    protected $storeResolver;

    /**
     * @var CategoryProcessor
     */
    protected $categoryProcessor;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var ProductWebsiteLink
     */
    protected $productWebsiteLink;

    /**
     * @var ProductAction
     */
    protected $productAction;

    /**
     * @var ResourceHelper
     */
    protected $resourceHelper;

    /**
     * @var string
     */
    protected $productEntityLinkField;

    /**
     * @var string
     */
    protected $productEntityIdentifierField;

    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    /**
     * ConfigurationVariations constructor.
     * @param StoreResolver $storeResolver
     * @param CategoryProcessor $categoryProcessor
     * @param ProductWebsiteLink $productWebsiteLink
     * @param ResourceConnection $resourceConnection
     * @param ProductAction $productAction
     * @param MetadataPool $metadataPool
     * @param ResourceHelper $resourceHelper
     */
    public function __construct(
        StoreResolver $storeResolver,
        CategoryProcessor $categoryProcessor,
        ProductWebsiteLink $productWebsiteLink,
        ResourceConnection $resourceConnection,
        ProductAction $productAction,
        MetadataPool $metadataPool,
        ResourceHelper $resourceHelper
    ) {
        $this->storeResolver = $storeResolver;
        $this->categoryProcessor = $categoryProcessor;
        $this->resourceConnection = $resourceConnection;
        $this->productWebsiteLink = $productWebsiteLink;
        $this->productAction = $productAction;
        $this->resourceHelper = $resourceHelper;
        $this->metadataPool = $metadataPool;
    }

    /**
     * @param array $data
     * @return $this
     * @throws \Exception
     */
    public function saveNewProduct(array &$data): self
    {
        $conn = $this->resourceConnection->getConnection();
        $conn->beginTransaction();
        try {
            $this->saveNewProductMain($data);
            $this->saveCategories($data);
            $this->saveWebsites($data);
            $this->saveAttributes($data);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
        return $this;
    }

    /**
     * @param $data
     * @return $this
     * @throws \Exception
     */
    public function updateProduct($data): self
    {
        $conn = $this->resourceConnection->getConnection();
        $conn->beginTransaction();
        try {
            $this->saveCategories($data);
            $this->saveWebsites($data);
            $this->saveAttributes($data);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
        return $this;
    }

    /**
     * @param int $id
     * @param array $data
     * @return $this
     * @throws \Exception
     */
    public function saveAttributes(array $data): self
    {
        if (empty($data['eav_attributes'])) {
            return $this;
        }
        $this->productAction->updateAttributes(
            [$data[$this->getProductIdentifierField()]],
            $data['eav_attributes'],
            Store::DEFAULT_STORE_ID
        );
        return $this;
    }

    /**
     * @param array $data
     * @return $this
     * @throws \Exception
     */
    protected function saveWebsites(array $data): self
    {
        $items = [];
        foreach ($data['website_ids'] as $websiteId) {
            $items[] = [$data[$this->getProductIdentifierField()], $websiteId];
        }
        if (!$items) {
            return $this;
        }
        $conn = $this->resourceConnection->getConnection();
        $conn->delete(
            $this->resourceConnection->getTableName('catalog_product_website'),
            $conn->quoteInto('product_id IN (?)', $data[$this->getProductIdentifierField()])
        );
        $conn->insertArray(
            $this->resourceConnection->getTableName('catalog_product_website'),
            ['product_id', 'website_id'],
            $items
        );
        return $this;
    }

    /**
     * @param array $data
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function saveNewProductMain(array &$data): self
    {
        $conn = $this->resourceConnection->getConnection();
        $data[$this->getProductIdentifierField()] = $this->generateIdentifier();
        $id = $this->resourceHelper->getNextAutoincrement(
            $this->resourceConnection->getTableName('catalog_product_entity')
        );
        $data[$this->getProductEntityLinkField()] = $id;
        $row = [
            $this->getProductIdentifierField() => $data[$this->getProductIdentifierField()],
            $this->getProductEntityLinkField() => $id,
            'attribute_set_id' => $data['attribute_set_id'],
            'type_id' => $data['type_id'],
            'sku' => $data[ImportProduct::COL_SKU],
        ];
        $conn->insert($this->resourceConnection->getTableName('catalog_product_entity'), $row);
        return $this;
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function getProductIdentifierField()
    {
        if (!$this->productEntityIdentifierField) {
            $this->productEntityIdentifierField = $this->metadataPool->getMetadata(ProductInterface::class)
                ->getIdentifierField();
        }
        return $this->productEntityIdentifierField;
    }

    /**
     * @return string|null
     * @throws \Exception
     */
    private function generateIdentifier()
    {
        return $this->metadataPool->getMetadata(ProductInterface::class)->generateIdentifier();
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function getProductEntityLinkField()
    {
        if (!$this->productEntityLinkField) {
            $this->productEntityLinkField = $this->metadataPool->getMetadata(ProductInterface::class)
                ->getLinkField();
        }
        return $this->productEntityLinkField;
    }

    /**
     * @param array $data
     * @return $this
     * @throws \Exception
     */
    protected function saveCategories(array $data): self
    {

        $items = [];
        if (!empty($data['category_ids'])) {
            foreach ($data['category_ids'] as $categoryId) {
                $items[] = [$data[$this->getProductIdentifierField()], $categoryId];
            }
        }
        if (!$items) {
            return $this;
        }
        $conn = $this->resourceConnection->getConnection();
        $conn->delete(
            $this->resourceConnection->getTableName('catalog_category_product'),
            $conn->quoteInto('product_id IN (?)', $data[$this->getProductIdentifierField()])
        );
        $conn->insertArray(
            $this->resourceConnection->getTableName('catalog_category_product'),
            ['product_id', 'category_id'],
            $items
        );
        return $this;
    }

    /**
     * @param array $rowData
     * @param string $separator
     * @return array
     */
    public function getWebsiteArray(array $rowData, string $separator):  array
    {
        if (empty($rowData[ImportProduct::COL_PRODUCT_WEBSITES])) {
            return $this->getWebsiteIdsBySku($rowData[ImportProduct::COL_SKU]) ?: [self::DEFAULT_WEBSITE_ID];
        }
        $result = [];
        $websiteCodes = explode($separator, $rowData[ImportProduct::COL_PRODUCT_WEBSITES]);
        foreach ($websiteCodes as $websiteCode) {
            $result[] = $this->storeResolver->getWebsiteCodeToId($websiteCode);
        }
        return $result;
    }

    /**
     * @param string $sku
     * @return array
     */
    protected function getWebsiteIdsBySku(string $sku):array
    {
        $conn = $this->resourceConnection->getConnection();
        $query = $conn->select()->from(
            ['cpw' => $this->resourceConnection->getTableName('catalog_product_website')],
            'cpw.website_id'
        )->join(
            ['cpe' => $this->resourceConnection->getTableName('catalog_product_entity')],
            'cpw.product_id = cpe.entity_id'
        )->where('cpe.sku=?', $sku);
        return $conn->fetchCol($query);
    }

    /**
     * @param array $rowData
     * @param string $separator
     * @return array
     */
    public function processRowCategories(array $rowData, string $separator)
    {
        $categoriesString = empty($rowData[ImportProduct::COL_CATEGORY]) ? '' : $rowData[ImportProduct::COL_CATEGORY];
        if (empty($categoriesString)) {
            return $this->getCategoryIdsBySku($rowData[ImportProduct::COL_SKU]);
        }
        return $categoryIds = $this->categoryProcessor->upsertCategories(
            $categoriesString,
            $separator
        );
    }

    /**
     * @param string $sku
     * @return array
     */
    protected function getCategoryIdsBySku(string $sku): array
    {
        $conn = $this->resourceConnection->getConnection();
        $query = $conn->select()->distinct()->from(
            ['ccp' => $this->resourceConnection->getTableName('catalog_category_product')],
            'ccp.category_id'
        )->join(
            ['cpe' => $this->resourceConnection->getTableName('catalog_product_entity')],
            'ccp.product_id = cpe.entity_id'
        )->where('cpe.sku=?', $sku);
        return $conn->fetchCol($query);
    }

    /**
     * @param array $rowData
     * @param array $cache
     * @return int
     */
    public function getAttributeSetIdBySku(array $rowData, array $cache): int
    {
        $attrSetCode = empty($rowData[ImportProduct::COL_ATTR_SET]) ? '' : $rowData[ImportProduct::COL_ATTR_SET];
        if (empty($attrSetCode)) {
            return $this->getAttrSetIdsBySku($rowData[ImportProduct::COL_SKU]);
        }
        return $cache[$attrSetCode];
    }

    /**
     * @param string $sku
     * @return int
     */
    protected function getAttrSetIdsBySku(string $sku): int
    {
        $conn = $this->resourceConnection->getConnection();
        $query = $conn->select()->from(
            ['ccp' => $this->resourceConnection->getTableName('catalog_product_entity')],
            'ccp.attribute_set_id'
        )->where('ccp.sku=?', $sku);
        return (int)$conn->fetchOne($query);
    }

    /**
     * @param int $id
     * @return $this
     * @throws \Exception
     */
    public function updateTypeProductToConfigurable(int $id): self
    {
        $conn = $this->resourceConnection->getConnection();
        $conn->update(
            $this->resourceConnection->getTableName('catalog_product_entity'),
            ['type_id' => TypeConfigurable::TYPE_CODE],
            $this->getProductIdentifierField() . ' = ' . $id
        );
        return $this;
    }

    /**
     * @param array $rowData
     * @param array $imageCodes
     * @return array
     */
    public function getAttrsImage(array $rowData, array $imageCodes): array
    {
        return $this->getImagesFromBd($rowData, array_diff($imageCodes, ['_media_image', 'video_url']));
    }

    /**
     * @param array $rowData
     * @param array $imageCodes
     * @return array
     */
    protected function getImagesFromBd(array $rowData, array $imageCodes): array
    {
        $result = [];
        foreach ($imageCodes as $code) {
            if (!empty($rowData[$code])) {
                $result[$code] = $rowData[$code];
            }
        }
        return $result;
    }
}
