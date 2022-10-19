<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export;

use Firebear\ImportExport\Model\Export\FilterProcessor\FilterProcessorAggregator;
use Firebear\ImportExport\Model\Export\SearchSynonyms\AttributeCollection;
use Firebear\ImportExport\Model\Export\SearchSynonyms\SynonymsInterface as Synonyms;
use Firebear\ImportExport\Model\ExportJob\Processor;
use Firebear\ImportExport\Traits\Export\Entity as ExportTrait;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\ImportExport\Model\Export;
use Magento\ImportExport\Model\Export\AbstractEntity;
use Magento\ImportExport\Model\Export\Factory as ExportFactory;
use Magento\ImportExport\Model\ResourceModel\CollectionByPagesIteratorFactory;
use Magento\Search\Model\ResourceModel\SynonymGroup\Collection as SynonymCollection;
use Magento\Search\Model\ResourceModel\SynonymGroup\CollectionFactory as SynonymCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class SearchSynonyms
 *
 * @package Firebear\ImportExport\Model\Export
 */
class SearchSynonyms extends AbstractEntity implements EntityInterface
{
    use ExportTrait;

    /**
     * Entity type code
     */
    const ENTITY_TYPE_CODE = 'search_synonyms';

    /**
     * Attribute collection name
     */
    const ATTRIBUTE_COLLECTION_NAME = AttributeCollection::class;

    /**
     * Extended filter types
     *
     * @var array
     */
    private $extTypeToFilterMapper = [
        Synonyms::WEBSITE_ID . ":" . Synonyms::STORE_ID => 'scope',
    ];

    /**
     * Search Synonyms CollectionFactory
     *
     * @var SynonymCollectionFactory
     */
    private $entityCollectionFactory;

    /**
     * Search Synonyms Collection
     *
     * @var SynonymCollection
     */
    private $entityCollection;

    /**
     * @var FilterProcessorAggregator
     */
    private $filterProcessor;

    /**
     * SearchSynonyms constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param ExportFactory $exportFactory
     * @param CollectionByPagesIteratorFactory $resourceColFactory
     * @param SynonymCollectionFactory $entityCollectionFactory
     * @param FilterProcessorAggregator $filterProcessor
     * @param ConsoleOutput $output
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ExportFactory $exportFactory,
        CollectionByPagesIteratorFactory $resourceColFactory,
        SynonymCollectionFactory $entityCollectionFactory,
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
            $this->lastEntityId,
        ];
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
        $columns = $this->getFieldsForExport();
        return $this->changeHeaders($columns);
    }

    /**
     * Retrieve entity collection
     *
     * @param bool $resetCollection
     * @return AbstractDb
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
    protected function _prepareEntityCollection(Collection $collection): Collection
    {
        $collection->setOrder(Synonyms::GROUP_ID, Collection::SORT_ORDER_ASC);

        if ($this->_parameters[Processor::LAST_ENTITY_SWITCH] > 0) {
            $lastEntityId = $this->_parameters[Processor::LAST_ENTITY_ID];
            $this->filterProcessor->process('lastentity', $collection, Synonyms::GROUP_ID, $lastEntityId);
        }

        $exportFilter = $this->retrieveFilterData($this->_parameters);
        if (!empty($exportFilter)) {
            $attributeCollection = $this->getAttributeCollection();
            $attributes = $attributeCollection->getItems();
            foreach ($exportFilter as $id => $value) {
                $id = $this->extTypeToFilterMapper[$id] ?? $id;
                if (isset($attributes[$id])) {
                    /** @var Attribute $attribute */
                    $attribute = $attributes[$id];
                    $attributeFilterType = $this->getAttributeFilterType($attribute);
                    $attributeCode = $attribute->getAttributeCode();
                    $this->filterProcessor->process($attributeFilterType, $collection, $attributeCode, $value);
                }
            }
        }

        return $collection;
    }

    /**
     * Retrieve filters data
     *
     * @param array $filters
     * @return array
     */
    private function retrieveFilterData(array $filters)
    {
        $filterData = array_filter(
            $filters[Processor::EXPORT_FILTER] ?? [],
            function ($value) {
                return $value !== '';
            }
        );

        return $filterData;
    }

    /**
     * Determine filter type for specified attribute.
     *
     * @param Attribute $attribute
     * @return string
     * @throws LocalizedException
     */
    private function getAttributeFilterType($attribute)
    {
        if (in_array($attribute->getAttributeCode(), array_keys($this->extTypeToFilterMapper))) {
            $filterType  = $this->extTypeToFilterMapper[$attribute->getAttributeCode()];
        } else {
            $filterType = Export::getAttributeFilterType($attribute);
        }

        return $filterType;
    }

    /**
     * Retrieve entity field for filter
     *
     * @return array
     */
    public function getFieldsForFilter()
    {
        $fields = [];
        /** @var AttributeCollection $attributeCollection */
        $attributeCollection = $this->getAttributeCollection();
        $attributes = $attributeCollection->getAttributesForFilter();
        /** @var Attribute $attribute */
        foreach ($attributes as $attribute) {
            $fields[] = [
                'label' => $attribute->getDefaultFrontendLabel(),
                'value' => $attribute->getAttributeCode(),
            ];
        }

        return [$this->getEntityTypeCode() => $fields];
    }

    /**
     * Retrieve entity field for export
     *
     * @return array
     */
    public function getFieldsForExport()
    {
        $fields = [];
        /** @var AttributeCollection $attributeCollection */
        $attributeCollection = $this->getAttributeCollection();
        $attributes = $attributeCollection->getAttributesForExport();
        /** @var Attribute $attribute */
        foreach ($attributes as $attribute) {
            $fields[] = $attribute->getAttributeCode();
        }

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
        $fields = [];
        /** @var AttributeCollection $attributeCollection */
        $attributeCollection = $this->getAttributeCollection();
        $attributes = $attributeCollection->getAttributesForFilter();
        /** @var Attribute $attribute */
        foreach ($attributes as $attribute) {
            $fields[] = [
                'field' => $attribute->getAttributeCode(),
                'type' => $attribute->getFrontendInput(),
                'select' => ($attribute->getSourceModel() && $source = $attribute->getSource())
                    ? $source->toOptionArray()
                    : [],
                'noCaption' => true,
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
