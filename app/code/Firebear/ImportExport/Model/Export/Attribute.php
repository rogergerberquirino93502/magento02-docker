<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export;

use DateTime;
use Exception;
use Firebear\ImportExport\Helper\Data as Helper;
use Firebear\ImportExport\Model\Export\Dependencies\Config as ExportConfig;
use Firebear\ImportExport\Model\ExportJob\Processor;
use Firebear\ImportExport\Model\Import\Attribute as ImportAttribute;
use Firebear\ImportExport\Model\Source\Factory as SourceFactory;
use Firebear\ImportExport\Traits\Export\Entity as ExportTrait;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute as EntityAttributeModel;
use Magento\Eav\Model\ResourceModel\Entity\Attribute as EntityAttributeResourceModel;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection as EntityAttributeCollection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\ImportExport\Model\Export\AbstractEntity;
use Magento\ImportExport\Model\Export\Factory as ExportFactory;
use Magento\ImportExport\Model\ResourceModel\CollectionByPagesIteratorFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Attribute export adapter
 */
class Attribute extends AbstractEntity implements EntityInterface
{
    use ExportTrait;

    /**
     * Attribute collection name
     */
    const ATTRIBUTE_COLLECTION_NAME = EntityAttributeCollection::class;

    /**
     * XML path to page size parameter
     */
    const XML_PATH_PAGE_SIZE = 'firebear_importexport/page_size/attribute';

    /**
     * Export config data
     *
     * @var array
     */
    protected $_exportConfig;

    /**
     * Source Factory
     *
     * @var SourceFactory
     */
    protected $_sourceFactory;

    /**
     * Helper
     *
     * @var Helper
     */
    protected $_helper;

    /**
     * Resource Model
     *
     * @var ResourceConnection
     */
    protected $_resourceModel;

    /**
     * DB connection
     *
     * @var AdapterInterface
     */
    protected $_connection;

    /**
     * Item export data
     *
     * @var array
     */
    protected $_exportData = [];

    /**
     * EAV config
     *
     * @var array
     */
    protected $_eavConfig;

    /**
     * Catalog product entity typeId
     *
     * @var int
     */
    protected $_entityTypeId;

    /**
     * value from filter
     *
     * @var
     */
    protected $attributeSetNameFilter;

    /**
     * value from filter for store_id
     *
     * @var
     */
    protected $filterStoreIdValue;

    /**
     * $_cachedOptionData[$attributeID][$storeId] = []
     * @var array
     */
    protected $_cachedOptionData = [];

    /**
     * @var array
     */
    protected $_cachedSetsData = [];

    /**
     * Initialize export
     *
     * @param LoggerInterface $logger
     * @param ConsoleOutput $output
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param ExportFactory $collectionFactory
     * @param CollectionByPagesIteratorFactory $resourceColFactory
     * @param ExportConfig $exportConfig
     * @param SourceFactory $sourceFactory
     * @param ResourceConnection $resource
     * @param Helper $helper
     * @param EavConfig $eavConfig
     * @param array $data
     */
    public function __construct(
        LoggerInterface $logger,
        ConsoleOutput $output,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ExportFactory $collectionFactory,
        CollectionByPagesIteratorFactory $resourceColFactory,
        ExportConfig $exportConfig,
        SourceFactory $sourceFactory,
        ResourceConnection $resource,
        Helper $helper,
        EavConfig $eavConfig,
        array $data = []
    ) {
        $this->_logger = $logger;
        $this->output = $output;
        $this->_exportConfig = $exportConfig->get();
        $this->_sourceFactory = $sourceFactory;
        $this->_resourceModel = $resource;
        $this->_connection = $resource->getConnection();
        $this->_helper = $helper;
        $this->_eavConfig = $eavConfig;

        parent::__construct(
            $scopeConfig,
            $storeManager,
            $collectionFactory,
            $resourceColFactory,
            $data
        );

        $this->_initStores();
    }

    /**
     * Retrieve entity type code
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        return 'attribute';
    }

    /**
     * Retrieve header columns
     *
     * @return array
     * @throws LocalizedException
     */
    public function _getHeaderColumns()
    {
        return $this->changeHeaders(
            array_keys($this->describeTable())
        );
    }

    /**
     * Retrieve attribute collection
     *
     * @return EntityAttributeCollection
     * @throws LocalizedException
     */
    protected function _getEntityCollection()
    {
        /** @var EntityAttributeCollection $attributeCollection */
        $attributeCollection = $this->getAttributeCollection();
        return $attributeCollection->setEntityTypeFilter($this->_getEntityTypeId());
    }

    /**
     * Get entity type id
     *
     * @return int
     * @throws LocalizedException
     */
    protected function _getEntityTypeId()
    {
        if (!$this->_entityTypeId) {
            $entityType = $this->_eavConfig->getEntityType('catalog_product');
            $this->_entityTypeId = $entityType->getId();
        }
        return $this->_entityTypeId;
    }

    /**
     * Export process
     *
     * @return array
     * @throws LocalizedException
     * @throws Exception
     */
    public function export()
    {
        //Execution time may be very long
        set_time_limit(0);

        $this->addLogWriteln(__('Begin Export'), $this->output);
        $this->addLogWriteln(__('Scope Data'), $this->output);

        $collection = $this->_getEntityCollection();
        $this->_prepareEntityCollection($collection);
        $this->_exportCollectionByPages($collection);
        // create export file
        return [
            $this->getWriter()->getContents(),
            $this->_processedEntitiesCount,
            $this->lastEntityId,
        ];
    }

    /**
     * Export one item
     *
     * @param EntityAttributeModel $item
     * @return void
     * @throws LocalizedException
     */
    public function exportItem($item)
    {
        $itemExported = false;
        foreach ($this->_getExportData($item) as $storeRow) {
            foreach ($storeRow as $row) {
                $this->addLogWriteln(
                    __(
                        'Export %1 for attributeSet %2 of storeID %3',
                        $row['attribute_code'],
                        $row['attribute_set'],
                        $row['store_id']
                    ),
                    $this->getOutput(),
                    'info'
                );
                $row = $this->changeRow($row);
                $this->getWriter()->writeRow($row);
                $itemExported = true;
            }
        }
        if ($itemExported) {
            $this->_processedEntitiesCount++;
        }
    }

    /**
     * Get export data for collection
     *
     * @param EntityAttributeModel $attribute
     * @return array
     */
    protected function _getExportData($attribute)
    {
        $filterStoreIdValue = $this->filterStoreIdValue;
        $this->_exportData = [];
        $attributeId = $attribute->getId();
        $code = $attribute->getAttributeCode();
        $this->lastEntityId = $attributeId;

        $setsData = $this->_getSetData($attributeId) ?: [];
        foreach ($setsData as $setData) {
            $row = [
                'store_id' => 0,
                'entity_type' => 'product',
                'attribute_set' => $setData['attribute_set_name'] ?? null,
                'group:name' => $setData['attribute_group_name'] ?? null,
                'group:sort_order' => $setData['sort_order'] ?? null,
            ];
            $row = array_merge($row, $attribute->toArray());
            unset(
                $row['attribute_id'],
                $row['entity_type_id']
            );
            $row['option:base_value'] = '';
            $row['option:value'] = '';
            $row['option:sort_order'] = '';
            $row['option:swatch_value'] = '';

            $exportData = [0 => $row];
            $pattern = array_fill_keys(array_keys($row), '');
            unset($row);

            $labels = $this->_getStoreLabels($attributeId);
            foreach ($labels as $storeId => $label) {
                $new = $pattern;
                $new['attribute_set'] = $setData['attribute_set_name'] ?? null;
                $new['attribute_code'] = $code;
                $new['store_id'] = $storeId;
                $new['frontend_label'] = $label;
                if ($filterStoreIdValue === null) {
                    $exportData[$storeId] = $new;
                } else {
                    if ($storeId == $filterStoreIdValue) {
                        $exportData[$storeId] = $new;
                    }
                }
            }

            ksort($exportData);
            $baseValue = [];
            foreach ($exportData as $exportStoreId => $row) {
                foreach ($this->_storeIdToCode as $storeId => $storeCode) {
                    if ($exportStoreId != $storeId && isset($exportData[$storeId])) {
                        continue;
                    }
                    $options = $this->_getOptionData($attributeId, $storeId);
                    if (0 < count($options)) {
                        $first = true;
                        foreach ($options as $option) {
                            $new = $first ? $row : $pattern;
                            $first = false;

                            $optionId = (int)$option['option_id'];

                            if (!isset($exportData[$storeId])) {
                                $new = $pattern;
                            }

                            if (0 == $storeId) {
                                $baseValue[$optionId] = $option['value'];
                            }
                            $new['attribute_set'] = $setData['attribute_set_name'] ?? null;
                            $new['attribute_code'] = $code;
                            $new['store_id'] = $storeId;
                            $new['option:base_value'] = ($storeId && isset($baseValue[$optionId])) ?
                                $baseValue[$optionId] :
                                '';
                            $new['option:value'] = $option['value'];
                            if ((isset($option['swatch_value']))) {
                                $new['option:swatch_value'] = $option['swatch_value'];
                            } else {
                                $new['option:swatch_value'] = null;
                            }
                            $new['option:sort_order'] = $option['sort_order'];
                            if ($filterStoreIdValue === null) {
                                $this->_exportData[$storeId][] = $new;
                            } else {
                                if ($storeId == $filterStoreIdValue) {
                                    $this->_exportData[$storeId][] = $new;
                                }
                            }
                        }
                    } elseif ($exportStoreId == $storeId) {
                        if ($filterStoreIdValue === null) {
                            $this->_exportData[$storeId][] = $row;
                        } else {
                            if ($storeId == $filterStoreIdValue) {
                                $this->_exportData[$storeId][] = $row;
                            }
                        }
                    }
                }
            }
        }

        return $this->_exportData;
    }

    /**
     * Get set data for attribute
     *
     * @param integer $attributeId
     * @return array
     */
    protected function _getSetData($attributeId)
    {
        if (!isset($this->_cachedSetsData[$attributeId])) {
            $resource = $this->_resourceModel;
            $table = $resource->getTableName('eav_entity_attribute');
            $setTable = $resource->getTableName('eav_attribute_set');
            $groupTable = $resource->getTableName('eav_attribute_group');

            $select = $this->_connection->select();
            $select->from(
                ['e' => $table],
                []
            )->join(
                ['s' => $setTable],
                'e.attribute_set_id = s.attribute_set_id',
                ['s.attribute_set_name']
            )->join(
                ['g' => $groupTable],
                'e.attribute_group_id = g.attribute_group_id',
                ['g.attribute_group_name', 'g.attribute_group_code', 'g.tab_group_code', 'g.sort_order']
            )->where(
                'e.attribute_id = ?',
                $attributeId
            );
            if (!empty($this->attributeSetNameFilter)) {
                $select->where(
                    's.attribute_set_name = ?',
                    $this->attributeSetNameFilter
                );
            }
            $this->_cachedSetsData[$attributeId] = $this->_connection->fetchAll($select);
        }

        return $this->_cachedSetsData[$attributeId];
    }

    /**
     * Get option data for attribute
     *
     * @param integer $attributeId
     * @param integer $storeId
     * @return array
     */
    protected function _getOptionData($attributeId, $storeId)
    {
        if (!isset($this->_cachedOptionData[$attributeId][$storeId])) {
            $resource = $this->_resourceModel;
            $optionTable = $resource->getTableName('eav_attribute_option');
            $optionValueTable = $resource->getTableName('eav_attribute_option_value');
            $swatchValueTable = $resource->getTableName('eav_attribute_option_swatch');

            $select = $this->_connection->select();
            $select->from(
                ['o' => $optionTable],
                ['o.option_id', 'o.sort_order']
            )->join(
                ['v' => $optionValueTable],
                'o.option_id = v.option_id',
                ['v.value']
            )->joinLeft(
                ['s' => $swatchValueTable],
                sprintf(
                    'o.option_id = s.option_id and s.store_id = %d',
                    $storeId
                ),
                ['swatch_value' => 's.value']
            )->where(
                'o.attribute_id = ?',
                $attributeId
            )->where(
                'v.store_id = ?',
                $storeId
            )->order('o.sort_order');
            $this->_cachedOptionData[$attributeId][$storeId] = $this->_connection->fetchAll($select);
        }
        return $this->_cachedOptionData[$attributeId][$storeId];
    }

    /**
     * Checks if nested structure
     *
     * @return bool
     */
    protected function _isNested()
    {
        return in_array(
            $this->_parameters['behavior_data']['file_format'],
            ['xml', 'json']
        );
    }

    /**
     * Apply filter to collection
     *
     * @param AbstractCollection $collection
     * @return AbstractCollection
     * @throws Exception
     */
    protected function _prepareEntityCollection(AbstractCollection $collection)
    {
        if (!empty($this->_parameters['last_entity_id']) &&
            $this->_parameters['enable_last_entity_id'] > 0
        ) {
            $collection->addFieldToFilter(
                'main_table.attribute_id',
                ['gt' => $this->_parameters['last_entity_id']]
            );
        }
        $collection->addFieldToFilter('additional_table.is_visible', 1);

        if (!isset($this->_parameters[Processor::EXPORT_FILTER_TABLE]) ||
            !is_array($this->_parameters[Processor::EXPORT_FILTER_TABLE])) {
            $exportFilter = [];
        } else {
            $exportFilter = $this->_parameters[Processor::EXPORT_FILTER_TABLE];
        }

        $filters = [];
        $entity = $this->getEntityTypeCode();
        foreach ($exportFilter as $data) {
            if ($data['entity'] == $entity) {
                $filters[$data['field']] = $data['value'];
            }
        }

        $fields = [];
        $columns = $this->getFieldColumns();
        foreach ($columns['attribute'] as $field) {
            $fields[$field['field']] = $field['type'];
        }

        foreach ($filters as $key => $value) {
            if (isset($fields[$key])) {
                $type = $fields[$key];
                if ($key == 'store_id') {
                    $this->filterStoreIdValue = $value;
                    continue;
                }
                if ($key == 'group:name') {
                    $key = 'eag.attribute_group_name';
                    $collection->getSelect()->joinLeft(
                        ['eea' => 'eav_entity_attribute'],
                        'main_table.attribute_id = eea.attribute_id',
                        ['eea.attribute_group_id']
                    )->joinLeft(
                        ['eag' => 'eav_attribute_group'],
                        'eea.attribute_group_id = eag.attribute_group_id',
                        ['eag.attribute_group_name']
                    )->group('main_table.attribute_id');
                }
                if ($key == 'attribute_set') {
                    $this->attributeSetNameFilter = $value;
                    $key = 'eas.attribute_set_name';
                    $collection->getSelect()->joinLeft(
                        ['ea' => 'eav_entity_attribute'],
                        'main_table.attribute_id = ea.attribute_id',
                        ['ea.attribute_set_id']
                    )->joinLeft(
                        ['eas' => 'eav_attribute_set'],
                        'ea.attribute_set_id = eas.attribute_set_id',
                        ['eas.attribute_set_name']
                    )->group('main_table.attribute_id');
                }

                if ('text' == $type) {
                    if (is_scalar($value)) {
                        trim($value);
                    }
                    $collection->addFieldToFilter($key, ['like' => "%{$value}%"]);
                } elseif ('select' == $type) {
                    $collection->addFieldToFilter($key, ['eq' => $value]);
                } elseif ('int' == $type) {
                    if (is_array($value) && count($value) == 2) {
                        $from = array_shift($value);
                        $to = array_shift($value);

                        if (is_numeric($from)) {
                            $collection->addFieldToFilter($key, ['from' => $from]);
                        }
                        if (is_numeric($to)) {
                            $collection->addFieldToFilter($key, ['to' => $to]);
                        }
                    }
                } elseif ('date' == $type) {
                    if (is_array($value) && count($value) == 2) {
                        $from = array_shift($exportFilter[$value]);
                        $to = array_shift($exportFilter[$value]);

                        if (is_scalar($from) && !empty($from)) {
                            $date = (new DateTime($from))->format('m/d/Y');
                            $collection->addFieldToFilter($key, ['from' => $date, 'date' => true]);
                        }
                        if (is_scalar($to) && !empty($to)) {
                            $date = (new DateTime($to))->format('m/d/Y');
                            $collection->addFieldToFilter($key, ['to' => $date, 'date' => true]);
                        }
                    }
                }
            }
        }
        return $collection;
    }

    /**
     * Retrieve store labels by given attribute id
     *
     * @param int $attributeId
     * @return array
     */
    protected function _getStoreLabels($attributeId)
    {
        /** @var EntityAttributeCollection $attributeCollection */
        $attributeCollection = $this->getAttributeCollection();
        /** @var EntityAttributeResourceModel $resource */
        $resource = $attributeCollection->getResource();
        return $resource->getStoreLabelsByAttributeId($attributeId);
    }

    /**
     * Retrieve entity field for export
     *
     * @return array
     * @throws LocalizedException
     */
    public function getFieldsForExport()
    {
        $fields = array_keys($this->describeTable());
        $fields = array_merge(ImportAttribute::getAdditionalColumns(), $fields);
        return $fields;
    }

    /**
     * Retrieve entity field columns
     *
     * @return array
     * @throws LocalizedException
     */
    public function getFieldColumns()
    {
        $options = [];
        foreach ($this->describeTable() as $key => $field) {
            if ($field == 'entity_type_id' || $field == 'is_visible') {
                continue;
            }
            $select = [];
            $type = $this->_helper->convertTypesTables($field['DATA_TYPE']);
            if ('int' == $type && (
                'is_' == substr($field['COLUMN_NAME'], 0, 3) ||
                'used_' == substr($field['COLUMN_NAME'], 0, 5)
            )) {
                $select[] = ['label' => __('Yes'), 'value' => 1];
                $select[] = ['label' => __('No'), 'value' => 0];
                $type = 'select';
            }
            $options['attribute'][] = ['field' => $key, 'type' => $type, 'select' => $select];
        }
        $options['attribute'][] = ['field' => 'store_id', 'type' => 'text', 'select' => []];
        $options['attribute'][] = ['field' => 'attribute_set', 'type' => 'text', 'select' => []];
        $options['attribute'][] = ['field' => 'group:name', 'type' => 'text', 'select' => []];
        $options['attribute'][] = ['field' => 'group:sort_order', 'type' => 'text', 'select' => []];
        return $options;
    }

    /**
     * Retrieve entity field for filter
     *
     * @return array
     * @throws LocalizedException
     */
    public function getFieldsForFilter()
    {
        $options = [];
        foreach ($this->getFieldsForExport() as $field) {
            $options[] = [
                'label' => $field,
                'value' => $field,
            ];
        }
        return [$this->getEntityTypeCode() => $options];
    }

    /**
     * Retrieve the column descriptions for a table, include additional table
     *
     * @return array
     * @throws LocalizedException
     */
    protected function describeTable()
    {
        /** @var EntityAttributeCollection $attributeCollection */
        $attributeCollection = $this->getAttributeCollection();
        /** @var EntityAttributeResourceModel $resource */
        $resource = $attributeCollection->getResource();
        $additionalTable = $resource->getAdditionalAttributeTable(
            $this->_getEntityTypeId()
        );
        $fields = $resource->describeTable($resource->getMainTable());
        $fields+= $resource->describeTable($this->_resourceModel->getTableName($additionalTable));

        unset($fields['attribute_id']);
        return $fields;
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
