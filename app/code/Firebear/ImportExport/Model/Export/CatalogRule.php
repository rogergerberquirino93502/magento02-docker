<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export;

use DateTime;
use Firebear\ImportExport\Model\ExportJob\Processor;
use Magento\CatalogRule\Model\ResourceModel\Rule\Collection as CatalogRuleCollection;
use Firebear\ImportExport\Traits\Export\Entity as ExportTrait;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use Magento\ImportExport\Model\Export\AbstractEntity;
use Magento\ImportExport\Model\Export\Factory as CollectionFactory;
use Magento\ImportExport\Model\ResourceModel\CollectionByPagesIteratorFactory;
use Magento\Rule\Model\ConditionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Catalog Rule export
 */
class CatalogRule extends AbstractEntity implements EntityInterface
{
    use ExportTrait;

    /**
     * Entity collection
     *
     * @var AbstractCollection
     */
    protected $_entityCollection;

    /**
     * Collection factory
     *
     * @var CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * Condition factory
     *
     * @var ConditionFactory
     */
    protected $conditionFactory;

    /**
     * Json serializer
     *
     * @var Serializer
     */
    protected $serializer;

    /**
     * Field list
     *
     * @var array
     */
    protected $fields = [
        'rule_id',
        'name',
        'description',
        'website_ids',
        'customer_group_ids',
        'from_date',
        'to_date',
        'conditions_serialized',
        'stop_rules_processing',
        'simple_action',
        'discount_amount',
        'sort_order',
        'is_active'
    ];

    /**
     * Initialize export
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $collectionFactory
     * @param CollectionByPagesIteratorFactory $resourceColFactory
     * @param ConditionFactory $conditionFactory
     * @param LoggerInterface $logger
     * @param ConsoleOutput $output
     * @param Serializer $serializer
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        CollectionFactory $collectionFactory,
        CollectionByPagesIteratorFactory $resourceColFactory,
        ConditionFactory $conditionFactory,
        LoggerInterface $logger,
        ConsoleOutput $output,
        Serializer $serializer,
        array $data = []
    ) {
        $this->_logger = $logger;
        $this->output = $output;
        $this->serializer = $serializer;
        $this->_collectionFactory = $collectionFactory;
        $this->conditionFactory = $conditionFactory;

        parent::__construct(
            $scopeConfig,
            $storeManager,
            $collectionFactory,
            $resourceColFactory,
            $data
        );
    }

    /**
     * Export process
     *
     * @return array
     * @throws LocalizedException
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
            $this->lastEntityId
        ];
    }

    /**
     * Retrieve entity collection
     *
     * @return AbstractCollection
     */
    protected function _getEntityCollection()
    {
        if (null === $this->_entityCollection) {
            $this->_entityCollection = $this->_collectionFactory->create(
                CatalogRuleCollection::class
            );
        }
        return $this->_entityCollection;
    }

    /**
     * Apply filter to collection
     *
     * @param AbstractCollection $collection
     * @return AbstractCollection
     * @throws LocalizedException
     * @throws \Exception
     */
    protected function _prepareEntityCollection(AbstractCollection $collection)
    {
        if (!empty($this->_parameters[Processor::LAST_ENTITY_ID]) &&
            $this->_parameters[Processor::LAST_ENTITY_SWITCH] > 0
        ) {
            $collection->addFieldToFilter(
                'main_table.rule_id',
                ['gt' => $this->_parameters[Processor::LAST_ENTITY_ID]]
            );
        }

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
        foreach ($columns['catalog_rule'] as $field) {
            $fields[$field['field']] = $field['type'];
        }

        foreach ($filters as $key => $value) {
            if (isset($fields[$key])) {
                $type = $fields[$key];
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
     * Entity type code getter
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        return 'catalog_rule';
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
        foreach ($this->fields as $field) {
            $select = [];
            $type = 'text';
            if ($field == 'from_date' || $field == 'to_date') {
                $type = 'date';
            }
            if ($field == 'rule_id' || $field == 'website_ids') {
                $type = 'int';
            }
            if ($field == 'is_active') {
                $type = 'select';
                $select[] = ['label' => __('Yes'), 'value' => 1];
                $select[] = ['label' => __('No'), 'value' => 0];
            }
            $options[$this->getEntityTypeCode()][] = [
                'field' => $field,
                'type' => $type,
                'select' => $select
            ];
        }
        return $options;
    }

    /**
     * Export one item
     *
     * @param AbstractModel $item
     * @return void
     * @throws LocalizedException
     */
    public function exportItem($item)
    {
        $data = [];
        $this->lastEntityId = $item->getId();

        foreach ($this->fields as $field) {
            $data[$field] = $item->getData($field);
        }

        $data['website_ids'] = implode(',', $data['website_ids']);
        $data['customer_group_ids'] = implode(',', $data['customer_group_ids']);

        if (!empty($data['conditions_serialized'])) {
            $conditions = $this->serializer->unserialize($data['conditions_serialized']);
            if (is_array($conditions)) {
                $data['conditions_serialized'] = $this->serializer->serialize(
                    $this->prepareConditions($conditions)
                );
            }
        }

        $row = $this->changeRow($data);
        $this->getWriter()->writeRow($row);
        $this->_processedEntitiesCount++;
    }

    /**
     * Prepare row conditions
     *
     * @param array $conditions
     * @return array
     */
    protected function prepareConditions(array $conditions)
    {
        if (!empty($conditions['type'])) {
            if ($this->validateModel($conditions['type'])) {
                if (!empty($conditions['attribute'])) {
                    $conditions['value'] = $this->prepareAttributeValue($conditions);
                }
            }
        }

        if (!empty($conditions['conditions']) && is_array($conditions['conditions'])) {
            foreach ($conditions['conditions'] as $key => $condition) {
                $conditions['conditions'][$key] = $this->prepareConditions($condition);
            }
        }
        return $conditions;
    }

    /**
     * Prepare conditions attribute value
     *
     * @param array $conditions
     * @return string|array
     */
    protected function prepareAttributeValue($conditions)
    {
        $condition = $this->conditionFactory->create($conditions['type']);
        $attributes = $condition->loadAttributeOptions()->getAttributeOption();

        if (isset($attributes[$conditions['attribute']])) {
            $condition->setAttribute($conditions['attribute']);
            if (in_array($condition->getInputType(), ['select', 'multiselect'])) {
                // reload options flag
                $condition->unsetData('value_select_options');
                $condition->unsetData('value_option');

                $options = $condition->getValueOption();
                if (is_array($conditions['value'])) {
                    foreach ($conditions['value'] as $key => $value) {
                        $conditions['value'][$key] = $options[$value];
                    }
                } else {
                    $conditions['value'] = $options[$conditions['value']];
                }
            }
        }
        return $conditions['value'];
    }

    /**
     * Validate conditions model
     *
     * @param string $model
     * @return bool
     */
    protected function validateModel($model)
    {
        return class_exists($model);
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
                'value' => $field
            ];
        }
        return [$this->getEntityTypeCode() => $options];
    }

    /**
     * Retrieve entity field for export
     *
     * @return array
     * @throws LocalizedException
     */
    public function getFieldsForExport()
    {
        return $this->fields;
    }

    /**
     * Retrieve header columns
     *
     * @return array
     */
    protected function _getHeaderColumns()
    {
        return $this->changeHeaders($this->fields);
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
