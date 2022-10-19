<?php
namespace Firebear\ImportExport\Model\Export;

use Exception;
use Firebear\ImportExport\Api\Data\ImportInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;
use Magento\ImportExport\Model\Export\Factory as ExportFactory;
use Magento\ImportExport\Model\ResourceModel\CollectionByPagesIteratorFactory;
use Firebear\ImportExport\Model\ResourceModel\Job\CollectionFactory as ImportJobsCollectionFactory;
use Firebear\ImportExport\Model\ExportJob\Processor;
use Firebear\ImportExport\Api\Data\ExportInterface;
use Firebear\ImportExport\Ui\Component\Listing\Column\Entity\Export\Options as EntityExportOptions;

/**
 * Class ImportJobs
 *
 * @package Firebear\ImportExport\Model\Export
 */
class ImportJobs extends AbstractJobs
{
    /**
     * @inheritdoc
     */
    const ENTITY_TYPE_CODE = 'import_jobs';

    /**
     * @var array
     */
    protected $_headerColumns = [
        ImportInterface::ENTITY_ID,
        ImportInterface::TITLE,
        ImportInterface::IS_ACTIVE,
        ImportInterface::CRON,
        ImportInterface::FREQUENCY,
        ImportInterface::ENTITY,
        ImportInterface::BEHAVIOR_DATA,
        ImportInterface::IMPORT_SOURCE,
        ImportInterface::SOURCE_DATA,
        ImportInterface::FILE_UPDATED_AT,
        ImportInterface::MAPPING,
        ImportInterface::PRICE_RULES,
        ImportInterface::XSLT,
        ImportInterface::TRANSLATE_FROM,
        ImportInterface::TRANSLATE_TO,
        ImportInterface::MAP,
    ];

    /**
     * @var array
     */
    protected $serializedColumns = [
        ImportInterface::BEHAVIOR_DATA,
        ImportInterface::SOURCE_DATA
    ];

    /**
     * @var Json
     */
    protected $jsonSerializer;

    /**
     * ImportJobs constructor.
     *
     * @param ImportJobsCollectionFactory $importJobsCollectionFactory
     * @inheritdoc
     */
    public function __construct(
        ImportJobsCollectionFactory $importJobsCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ExportFactory $collectionFactory,
        CollectionByPagesIteratorFactory $resourceColFactory,
        EntityExportOptions $entityExportOptions,
        Json $jsonSerializer,
        array $data = []
    ) {
        $this->setEntityCollection($importJobsCollectionFactory, $data);
        $this->jsonSerializer = $jsonSerializer;

        parent::__construct(
            $scopeConfig,
            $storeManager,
            $collectionFactory,
            $resourceColFactory,
            $entityExportOptions,
            $data
        );
    }

    /**
     * Retrieve adapter name
     *
     * @return string
     */
    public function getName()
    {
        return __('Import Jobs');
    }

    /**
     * Apply filter to collection
     *
     * @param AbstractCollection $collection
     * @return AbstractCollection
     * @throws Exception
     */
    protected function _prepareEntityCollection($collection)
    {
        if (isset($this->_parameters['last_entity_id'])
            && $this->_parameters['last_entity_id'] > 0
            && $this->_parameters['enable_last_entity_id'] > 0
        ) {
            $collection->addFieldToFilter(
                'entity_id',
                ['gt' => $this->_parameters['last_entity_id']]
            );
        }

        if (!isset($this->_parameters[Processor::EXPORT_FILTER_TABLE])
            || !is_array($this->_parameters[Processor::EXPORT_FILTER_TABLE])
        ) {
            $exportFilter = [];
        } else {
            $exportFilter = $this->_parameters[Processor::EXPORT_FILTER_TABLE];
        }

        $filters = [];
        foreach ($exportFilter as $data) {
            if ($data['entity'] == self::ENTITY_TYPE_CODE) {
                $filters[$data['field']] = $data['value'];
            }
        }

        foreach ($filters as $filterType => $filterValue) {
            if ($filterType == 'entity') {
                $collection->addFieldToFilter(ExportInterface::ENTITY, ['eq' => $filterValue]);
            } elseif ($filterType == 'entity_id') {
                $jobIds = $this->getAllIdValues($filterValue);
                if (count($jobIds)) {
                    $collection->addFieldToFilter(
                        ExportInterface::ENTITY_ID,
                        ['in' => implode(',', $jobIds)]
                    );
                }
            }
        }

        return $collection;
    }

    /**
     * @inheritdoc
     */
    protected function getJobsCollectionKey()
    {
        return 'import_jobs_collection';
    }

    /**
     * Get export data for collection
     *
     * @param AbstractModel $item
     * @return array
     */
    protected function getExportData($item)
    {
        $exportData = $item->toArray();
        foreach ($this->serializedColumns as $field) {
            $exportData[$field] = $this->jsonSerializer->serialize($exportData[$field]);
        }
        if (!empty($item->getMap())) {
            $exportData[ImportInterface::MAP] = $this->getMapData($item);
        }

        return $exportData;
    }

    /**
     * Get map data for job item
     *
     * @param AbstractModel $item
     * @return string
     */
    protected function getMapData($item)
    {
        $mapData = [];
        foreach ($item->getMap() as $map) {
            array_push($mapData, $map->getData());
        }
        return $this->jsonSerializer->serialize($mapData);
    }
}
