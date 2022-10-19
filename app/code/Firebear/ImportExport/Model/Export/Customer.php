<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export;

use Firebear\ImportExport\Traits\Export\Entity as ExportTrait;
use Firebear\ImportExport\Model\Export\Customer\Additional;
use Magento\CustomerImportExport\Model\Export\Customer as MagentoCustomer;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Eav\Model\Config;
use Magento\Store\Model\StoreManagerInterface;
use Magento\ImportExport\Model\Export\Factory;
use Magento\ImportExport\Model\ResourceModel\CollectionByPagesIteratorFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Customer
 *
 * @package Firebear\ImportExport\Model\Export
 */
class Customer extends MagentoCustomer implements EntityInterface
{
    use ExportTrait;

    /**
     * @var Additional
     */
    protected $additional;

    /**
     * Attribute labels
     *
     * @var array
     */
    protected $attributeLabels = [];

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Factory $collectionFactory
     * @param CollectionByPagesIteratorFactory $resourceColFactory
     * @param TimezoneInterface $localeDate
     * @param Config $eavConfig
     * @param CollectionFactory $customerColFactory
     * @param Additional $additional
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Factory $collectionFactory,
        CollectionByPagesIteratorFactory $resourceColFactory,
        TimezoneInterface $localeDate,
        Config $eavConfig,
        CollectionFactory $customerColFactory,
        Additional $additional,
        array $data = []
    ) {
        $this->additional = $additional;

        parent::__construct(
            $scopeConfig,
            $storeManager,
            $collectionFactory,
            $resourceColFactory,
            $localeDate,
            $eavConfig,
            $customerColFactory,
            $data
        );
    }

    /**
     * @return mixed
     */
    public function getFieldsForExport()
    {
        $validAttributeCodes = $this->_getExportAttributeCodes();

        return array_unique(array_merge($this->_permanentAttributes, $validAttributeCodes, ['password']));
    }

    /**
     * Retrieve entity field for filter
     *
     * @return array
     */
    public function getFieldsForFilter()
    {
        $fields = $this->additional->toOptionArray();
        foreach ($this->attributeLabels as $value => $label) {
            if ($value == 'tier_price') {
                continue;
            }
            $fields[] = ['value' => $value, 'label' => $label];
        }
        return [$this->getEntityTypeCode() => $fields];
    }

    /**
     * Retrieve entity field columns
     *
     * @return array
     * @throws LocalizedException
     */
    public function getFieldColumns()
    {
        $fields = $this->additional->getAdditionalFields();
        foreach ($this->attributeTypes as $field => $type) {
            $option = [];
            foreach ($this->_attributeValues[$field] ?? [] as $value => $label) {
                $option[] = ['value' => $value, 'label' => $label];
            }
            $fields[] = [
                'field' => $field,
                'type' => $this->getAttributeType($type),
                'select' => $option
            ];
        }
        return [$this->getEntityTypeCode() => $fields];
    }

    /**
     * Initializes attribute types
     *
     * @return $this
     */
    protected function _initAttributeTypes()
    {
        $this->initAttributeLabels();
        return parent::_initAttributeTypes();
    }

    /**
     * Initializes attribute labels
     *
     * @return $this
     */
    protected function initAttributeLabels()
    {
        /** @var $attribute AbstractAttribute */
        foreach ($this->getAttributeCollection() as $attribute) {
            $this->attributeLabels[$attribute->getAttributeCode()] = $attribute->getFrontendLabel();
        }
        return parent::_initAttributeTypes();
    }

    /**
     * @param $item
     * @throws LocalizedException
     */
    public function exportItem($item)
    {
        $row = $this->_addAttributeValuesToRow($item);
        $row[self::COLUMN_WEBSITE] = $this->_websiteIdToCode[$item->getWebsiteId()];
        $row[self::COLUMN_STORE] = $this->_storeIdToCode[$item->getStoreId()];

        if ($row['gender'] == "0") {
            $row['gender'] = '';
        }
        if ($this->_parameters['enable_last_entity_id'] > 0) {
            $this->lastEntityId = $item->getEntityId();
        }

        $this->getWriter()->writeRow($this->changeRow($row));
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function export()
    {
        $entityCollection = $this->_getEntityCollection();
        if (isset($this->_parameters['last_entity_id'])
            && $this->_parameters['last_entity_id'] > 0
            && $this->_parameters['enable_last_entity_id'] > 0
        ) {
            $entityCollection->addFieldToFilter(
                'entity_id',
                ['gt' => $this->_parameters['last_entity_id']]
            );
        }
        $this->_prepareEntityCollection($entityCollection);
        $writer = $this->getWriter();

        // create export file
        $writer->setHeaderCols($this->_getHeaderColumns());
        $this->_exportCollectionByPages($entityCollection);

        return [$writer->getContents(), $entityCollection->getSize(), $this->lastEntityId];
    }

    /**
     * @return array
     */
    protected function _getHeaderColumns()
    {
        $validAttributeCodes = $this->_getExportAttributeCodes();
        $headers = array_merge($this->_permanentAttributes, $validAttributeCodes, ['password']);

        return $this->changeHeaders($headers);
    }

    /**
     * Apply filter to collection and add not skipped attributes to select
     *
     * @param AbstractCollection $collection
     * @return AbstractCollection
     */
    protected function _prepareEntityCollection(AbstractCollection $collection)
    {
        $this->filterEntityCollection($collection);
        $this->_addAttributesToCollection($collection);
        return $collection;
    }

    /**
     * Retrieve attributes codes which are appropriate for export
     *
     * @return array
     */
    protected function _getExportAttrCodes()
    {
        return [];
    }
}
