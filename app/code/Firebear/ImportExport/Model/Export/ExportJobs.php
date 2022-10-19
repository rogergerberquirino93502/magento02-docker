<?php

namespace Firebear\ImportExport\Model\Export;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;
use Magento\ImportExport\Model\Export\Factory as ExportFactory;
use Magento\ImportExport\Model\ResourceModel\CollectionByPagesIteratorFactory;
use Firebear\ImportExport\Model\ResourceModel\ExportJob\CollectionFactory as ExportJobsCollectionFactory;
use Firebear\ImportExport\Model\ExportJob\Processor;
use Firebear\ImportExport\Api\Data\ExportInterface;
use Firebear\ImportExport\Ui\Component\Listing\Column\Entity\Export\Options as EntityExportOptions;

/**
 * Class ExportJobs
 *
 * @package Firebear\ImportExport\Model\Export
 */
class ExportJobs extends AbstractJobs
{
    /**
     * Entity type code
     */
    const ENTITY_TYPE_CODE = 'export_jobs';

    /**
     * @var array
     */
    protected $_headerColumns = [
        ExportInterface::ENTITY_ID,
        ExportInterface::TITLE,
        ExportInterface::IS_ACTIVE,
        ExportInterface::CRON,
        ExportInterface::FREQUENCY,
        ExportInterface::ENTITY,
        ExportInterface::BEHAVIOR_DATA,
        ExportInterface::EXPORT_SOURCE,
        ExportInterface::SOURCE_DATA,
        ExportInterface::FILE_UPDATED_AT,
        ExportInterface::XSLT,
        ExportInterface::EVENT,
    ];

    /**
     * @var array
     */
    protected $serializedColumns = [
        ExportInterface::BEHAVIOR_DATA,
        ExportInterface::EXPORT_SOURCE,
        ExportInterface::SOURCE_DATA
    ];

    /**
     * @var Json
     */
    protected $jsonSerializer;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param ExportFactory $collectionFactory
     * @param CollectionByPagesIteratorFactory $resourceColFactory
     * @param ExportJobsCollectionFactory $exportJobsCollectionFactory
     * @param EntityExportOptions $entityExportOptions
     * @param Json $jsonSerializer
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ExportFactory $collectionFactory,
        CollectionByPagesIteratorFactory $resourceColFactory,
        ExportJobsCollectionFactory $exportJobsCollectionFactory,
        EntityExportOptions $entityExportOptions,
        Json $jsonSerializer,
        array $data = []
    ) {
        $this->setEntityCollection($exportJobsCollectionFactory, $data);
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
        return __('Export Jobs');
    }

    /**
     * @inheritdoc
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
        return 'export_jobs_collection';
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
        if (!empty($item->getEvent())) {
            $exportData[ExportInterface::EVENT] = $this->getEventData($item);
        }

        return $exportData;
    }

    /**
     * Get event data for job item
     *
     * @param AbstractModel $item
     * @return string
     */
    protected function getEventData($item)
    {
        $eventData = [];
        foreach ($item->getEvent() as $event) {
            array_push($eventData, $event->getData());
        }
        return $this->jsonSerializer->serialize($eventData);
    }
}
