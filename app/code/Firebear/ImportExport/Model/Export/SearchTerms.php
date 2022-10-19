<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export;

use Firebear\ImportExport\Model\Export\SearchTerms\AttributeCollection;
use Firebear\ImportExport\Model\ExportJob\Processor;
use Firebear\ImportExport\Model\Export\FilterProcessor\FilterProcessorAggregator;
use Firebear\ImportExport\Traits\Export\Entity as ExportTrait;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\ImportExport\Model\Export;
use Magento\ImportExport\Model\Export\Factory as ExportFactory;
use Magento\ImportExport\Model\Export\AbstractEntity;
use Magento\ImportExport\Model\ResourceModel\CollectionByPagesIteratorFactory;
use Magento\Search\Model\ResourceModel\Query\CollectionFactory as QueryCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class SearchTerms
 *
 * @package Firebear\ImportExport\Model\Export
 */
class SearchTerms extends AbstractEntity implements EntityInterface
{
    use ExportTrait;

    /**
     * Entity type code
     */
    const ENTITY_TYPE_CODE = 'search_query';

    /**
     * Search Query Id field
     */
    const QUERY_ID = 'query_id';

    /**
     * Attribute collection name
     */
    const ATTRIBUTE_COLLECTION_NAME = AttributeCollection::class;

    /**
     * Search Query CollectionFactory
     *
     * @var QueryCollectionFactory
     */
    protected $entityCollectionFactory;

    /**
     * Search Query Collection
     */
    protected $entityCollection;

    /**
     * @var FilterProcessorAggregator
     */
    private $filterProcessor;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param ExportFactory $exportFactory
     * @param CollectionByPagesIteratorFactory $resourceColFactory
     * @param QueryCollectionFactory $entityCollectionFactory
     * @param FilterProcessorAggregator $filterProcessor
     * @param ConsoleOutput $output
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ExportFactory $exportFactory,
        CollectionByPagesIteratorFactory $resourceColFactory,
        QueryCollectionFactory $entityCollectionFactory,
        FilterProcessorAggregator $filterProcessor,
        ConsoleOutput $output,
        array $data = []
    ) {
        $this->entityCollectionFactory = $entityCollectionFactory;
        $this->filterProcessor = $filterProcessor;
        $this->output = $output;
        parent::__construct($scopeConfig, $storeManager, $exportFactory, $resourceColFactory, $data);
    }

    /**
     * Entity type code getter
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        return self::ENTITY_TYPE_CODE;
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

        $writer = $this->getWriter();
        $writer->setHeaderCols($this->_getHeaderColumns());

        $collection = $this->_getEntityCollection(true);
        $this->_prepareEntityCollection($collection);
        $this->_exportCollectionByPages($collection);

        return [
            $writer->getContents(),
            $this->_processedEntitiesCount,
            $this->lastEntityId
        ];
    }

    /**
     * Export one item
     *
     * @param AbstractModel $item
     * @throws LocalizedException
     */
    public function exportItem($item)
    {
        $exportData = $this->changeRow($item->getData());
        $this->getWriter()->writeRow($exportData);
        $this->lastEntityId = $item->getId();
        $this->_processedEntitiesCount++;
    }

    /**
     * Retrieve header columns
     *
     * @return array
     */
    protected function _getHeaderColumns()
    {
        $columns = [];
        $attributeCollection = $this->getAttributeCollection();
        /** @var Attribute $attribute */
        foreach ($attributeCollection as $attribute) {
            $columns[] = $attribute->getAttributeCode();
        }

        return $this->changeHeaders($columns);
    }

    /**
     * Retrieve entity collection
     *
     * @param bool $resetCollection
     * @return AbstractCollection
     */
    protected function _getEntityCollection($resetCollection = false)
    {
        if ($resetCollection || empty($this->entityCollection)) {
            $this->entityCollection = $this->entityCollectionFactory->create();
        }

        return $this->entityCollection;
    }

    /**
     * Apply filter to collection
     *
     * @param Collection $collection
     * @return Collection
     * @throws LocalizedException
     */
    protected function _prepareEntityCollection(AbstractCollection $collection)
    {
        if (!empty($this->_parameters[Processor::LAST_ENTITY_ID]) &&
            $this->_parameters[Processor::LAST_ENTITY_SWITCH] > 0
        ) {
            $collection->addFieldToFilter(
                'main_table.query_id',
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
        foreach ($columns['search_query'] as $field) {
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
                        $from = array_shift($value);
                        $to = array_shift($value);

                        if (is_scalar($from) && !empty($from)) {
                            $date = (new \DateTime($from))->format('m/d/Y');
                            $collection->addFieldToFilter($key, ['from' => $date, 'date' => true]);
                        }
                        if (is_scalar($to) && !empty($to)) {
                            $date = (new \DateTime($to))->format('m/d/Y');
                            $collection->addFieldToFilter($key, ['to' => $date, 'date' => true]);
                        }
                    }
                }
            }
        }
        return $collection;
    }

    /**
     * Retrieve entity field for export
     *
     * @return array
     */
    public function getFieldsForExport()
    {
        $fields = [];
        $attributeCollection = $this->getAttributeCollection();
        /** @var Attribute $attribute */
        foreach ($attributeCollection as $attribute) {
            $fields[] = $attribute->getAttributeCode();
        }

        return $fields;
    }

    /**
     * Retrieve entity field for filter
     *
     * @return array
     */
    public function getFieldsForFilter()
    {
        $fields = [];
        $attributeCollection = $this->getAttributeCollection();
        /** @var Attribute $attribute */
        foreach ($attributeCollection as $attribute) {
            $label = $attribute->getDefaultFrontendLabel();
            if (($label != 'Is Active') && ($label != 'Is Processed')) {
                $fields[] = [
                    'label' => $label,
                    'value' => $attribute->getAttributeCode()
                ];
            }
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
        $fields = [];
        $attributeCollection = $this->getAttributeCollection();
        /** @var Attribute $attribute */
        foreach ($attributeCollection as $attribute) {
            /** @var AbstractSource $source */
            $fields[] = [
                'field'     => $attribute->getAttributeCode(),
                'type'      => $attribute->getFrontendInput(),
                'select'    => ($attribute->getSourceModel() && $source = $attribute->getSource())
                    ? $source->toOptionArray()
                    : []
            ];
        }

        return [$this->getEntityTypeCode() => $fields];
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
