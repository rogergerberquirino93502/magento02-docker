<?php
namespace Firebear\ImportExport\Model\Export;

use Magento\ImportExport\Model\Export\AbstractEntity;
use Firebear\ImportExport\Traits\Export\Entity as ExportTrait;
use Magento\Framework\Exception\LocalizedException;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Firebear\ImportExport\Ui\Component\Listing\Column\Entity\Export\Options as EntityExportOptions;
use Magento\ImportExport\Model\ResourceModel\CollectionByPagesIteratorFactory;
use Magento\ImportExport\Model\Export\Factory as ExportFactory;
use Firebear\ImportExport\Model\ResourceModel\Job\CollectionFactory as JobCollectionFactory;
use Firebear\ImportExport\Model\ResourceModel\ExportJob\CollectionFactory as ExportJobCollectionFactory;
use Magento\Framework\Model\AbstractModel;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Exception;

/**
 * Class AbstractJobs
 * @package Firebear\ImportExport\Model\Export
 */
abstract class AbstractJobs extends AbstractEntity implements EntityInterface
{
    use ExportTrait;

    /**
     * @var array
     */
    protected $_headerColumns = [];

    /**
     * @var EntityExportOptions
     */
    protected $entityExportOptions;

    /**
     * @var JobCollectionFactory
     */
    protected $entityCollection;

    /**
     * Entity type code
     */
    const ENTITY_TYPE_CODE = 'jobs';

    /**
     * AbstractJobs constructor.
     *
     * @param EntityExportOptions $entityExportOptions
     * @param JobCollectionFactory|ExportJobCollectionFactory $collectionFactory
     * @inheritdoc
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ExportFactory $collectionFactory,
        CollectionByPagesIteratorFactory $resourceColFactory,
        EntityExportOptions $entityExportOptions,
        array $data = []
    ) {
        $this->entityExportOptions = $entityExportOptions;

        parent::__construct(
            $scopeConfig,
            $storeManager,
            $collectionFactory,
            $resourceColFactory,
            $data
        );
    }

    /**
     * Retrieve entity type code
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        return static::ENTITY_TYPE_CODE;
    }

    /**
     * Retrieve header columns
     *
     * @return array
     */
    public function _getHeaderColumns()
    {
        return $this->_headerColumns;
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
        set_time_limit(0);

        $this->addLogWriteln(__('Begin Export'), $this->output);
        $this->addLogWriteln(__('Scope Data'), $this->output);

        /** @var AbstractCollection $collection */
        $collection = $this->_getEntityCollection();
        $this->_prepareEntityCollection($collection);
        $this->_exportCollectionByPages($collection);

        return [
            $this->getWriter()->getContents(),
            $this->_processedEntitiesCount,
            $this->lastEntityId
        ];
    }

    protected function getAllIdValues($filterValue)
    {
        $ids = [];
        $values = explode(',', $filterValue);
        foreach ($values as $value) {
            if (strripos($value, '-') !== false) {
                $numbers = explode('-', $value);
                $ids = array_merge($ids, range($numbers[0], $numbers[1]));
            } else {
                $ids[] = $value;
            }
        }

        return $ids;
    }

    /**
     * Retrieve entity field for filter
     *
     * @return array
     * @throws LocalizedException
     */
    public function getFieldsForFilter()
    {
        $options = [
            'entity_id' => [
                'label' => 'Entity ID',
                'value' => 'entity_id'
            ],
            'entity' => [
                'label' => 'Entity',
                'value' => 'entity'
            ]
        ];

        return [$this->getEntityTypeCode() => $options];
    }

    /**
     * Retrieve entity field columns
     *
     * @return array
     * @throws LocalizedException
     */
    public function getFieldColumns()
    {
        $options = [
            [
                'field'     => 'entity_id',
                'type'      => 'text'
            ],
            [
                'field'     => 'entity',
                'type'      => 'select',
                'select'    => $this->entityExportOptions->toOptionArray()
            ]
        ];

        return [$this->getEntityTypeCode() => $options];
    }

    /**
     * @param JobCollectionFactory|ExportJobCollectionFactory $factory
     * @param array $data
     */
    protected function setEntityCollection($factory, $data)
    {
        $this->entityCollection = $data[$this->getJobsCollectionKey()]
            ?? $factory->create();
    }

    /**
     * @inheritdoc
     */
    protected function _getEntityCollection()
    {
        return $this->entityCollection;
    }

    /**
     * Add one item export data
     *
     * @param AbstractModel $item
     * @throws LocalizedException
     */
    public function exportItem($item)
    {
        $this->getWriter()->writeRow($this->getExportData($item));
        $this->_processedEntitiesCount++;
    }

    /**
     * Get export data for collection
     *
     * @param AbstractModel $item
     * @return array
     */
    protected function getExportData($item)
    {
        return $item->toArray();
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

    /**
     * Get jobs collection key
     *
     * @return string
     */
    abstract protected function getJobsCollectionKey();

    /**
     * Apply filter to collection
     *
     * @param AbstractCollection $collection
     * @return AbstractCollection
     */
    abstract protected function _prepareEntityCollection($collection);
}
