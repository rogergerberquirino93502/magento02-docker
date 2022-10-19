<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Export;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\ImportExport\Model\Export\AbstractEntity;
use Magento\ImportExport\Model\Export\Factory as CollectionFactory;
use Magento\ImportExport\Model\ResourceModel\CollectionByPagesIteratorFactory;
use Magento\Review\Model\ResourceModel\Review\Status\Collection as StatusCollection;
use Magento\Review\Model\ResourceModel\Rating\Option\Vote\CollectionFactory as VoteCollectionFactory;
use Magento\Review\Model\ResourceModel\Rating\CollectionFactory as RatingCollectionFactory;
use Magento\Review\Model\Review as ReviewModel;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ResourceConnection;
use Symfony\Component\Console\Output\ConsoleOutput;
use Psr\Log\LoggerInterface;
use Firebear\ImportExport\Traits\Export\Entity as ExportTrait;
use Firebear\ImportExport\Model\ExportJob\Processor;
use Firebear\ImportExport\Model\ResourceModel\Review\Collection as ReviewCollection;
use Magento\Eav\Api\AttributeRepositoryInterface;

/**
 * Review export
 */
class Review extends AbstractEntity implements EntityInterface
{
    use ExportTrait;

    /**
     * Console Output
     *
     * @var \Symfony\Component\Console\Output\ConsoleOutput
     */
    protected $output;

    /**
     * Logger Interface
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * Entity collection
     *
     * @var \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    protected $entityCollection;

    /**
     * Status map
     *
     * @var array
     */
    protected $statusMap;

    /**
     * Collection factory
     *
     * @var \Magento\ImportExport\Model\Export\Factory
     */
    protected $collectionFactory;

    /**
     * Vote collection factory
     *
     * @var \Magento\Review\Model\ResourceModel\Rating\Option\Vote\CollectionFactory
     */
    protected $voteCollectionFactory;

    /**
     * Rating collection factory
     *
     * @var \Magento\Review\Model\ResourceModel\Rating\CollectionFactory
     */
    protected $ratingCollectionFactory;

    /**
     * Rating fields
     *
     * @var array
     */
    protected $ratingFields;

    /**
     * Export fields
     *
     * @var array
     */
    protected $exportFields = [
        'review_id',
        'sku',
        'nickname',
        'title',
        'detail',
        'status',
        'created_at',
        'product_name'
    ];

    /**
     * Product entity link field
     *
     * @var string
     */
    private $productEntityLinkField;

    /**
     * Resource Model
     *
     * @var ResourceConnection
     */
    protected $_resourceModel;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var AttributeRepositoryInterface
     */
    protected $attributeRepository;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $collectionFactory
     * @param CollectionByPagesIteratorFactory $resourceColFactory
     * @param LoggerInterface $logger
     * @param ConsoleOutput $output
     * @param VoteCollectionFactory $voteCollectionFactory
     * @param RatingCollectionFactory $ratingCollectionFactory
     * @param ResourceConnection $resource
     * @param AttributeRepositoryInterface $attributeRepository
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        CollectionFactory $collectionFactory,
        CollectionByPagesIteratorFactory $resourceColFactory,
        LoggerInterface $logger,
        ConsoleOutput $output,
        VoteCollectionFactory $voteCollectionFactory,
        RatingCollectionFactory $ratingCollectionFactory,
        ResourceConnection $resource,
        AttributeRepositoryInterface $attributeRepository,
        MetadataPool $metadataPool,
        array $data = []
    ) {
        $this->_logger = $logger;
        $this->output = $output;
        $this->collectionFactory = $collectionFactory;
        $this->voteCollectionFactory = $voteCollectionFactory;
        $this->_resourceModel = $resource;
        $this->ratingCollectionFactory = $ratingCollectionFactory;
        $this->storeManager = $storeManager;
        $this->attributeRepository = $attributeRepository;
        $this->metadataPool = $metadataPool;
        parent::__construct(
            $scopeConfig,
            $storeManager,
            $collectionFactory,
            $resourceColFactory,
            $data
        );

        $this->initRating();
    }

    /**
     * Export process
     *
     * @return array
     */
    public function export()
    {
        //Execution time may be very long
        set_time_limit(0);

        $this->addLogWriteln(__('Begin Export'), $this->output);
        $this->addLogWriteln(__('Scope Data'), $this->output);

        $collection = $this->_getEntityCollection();
        $this->_prepareEntityCollection($collection);
        $writer = $this->getWriter();
        // create export file
        $writer->setHeaderCols($this->_getHeaderColumns());
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
     * @param \Magento\Framework\Model\AbstractModel $item
     * @return void
     */
    public function exportItem($item)
    {
        $this->lastEntityId = $item->getId();
        $rowData = $item->toArray($this->getFieldsForExport());
        $statusMap = $this->getStatusMap();

        $rowData['status'] = $statusMap[$item->getStatusId()] ?? '';
        $votes = $this->voteCollectionFactory->create()
            ->setReviewFilter($item->getId())
            ->addRatingInfo();

        $ratingData = array_fill_keys($this->ratingFields, '');
        foreach ($votes as $vote) {
            $field = $this->ratingFields[$vote->getRatingId()] ?? null;
            if (null !== $field) {
                $ratingData[$field] = $vote->getValue();
            }
        }

        $rowData = array_merge($rowData, $ratingData);
        $this->getWriter()->writeRow(
            $this->changeRow($rowData)
        );
        $this->_processedEntitiesCount++;
    }

    /**
     * Entity type code getter
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        return 'review';
    }

    /**
     * Retrieve header columns
     *
     * @return array
     */
    protected function _getHeaderColumns()
    {
        return $this->changeHeaders(
            $this->getFieldsForExport()
        );
    }

    /**
     * Init rating
     *
     * @return array
     */
    protected function initRating()
    {
        if (null === $this->ratingFields) {
            $collection = $this->ratingCollectionFactory->create()
                ->addEntityFilter(ReviewModel::ENTITY_PRODUCT_CODE);
            foreach ($collection as $rating) {
                $this->ratingFields[$rating->getId()] = 'vote:' . $rating->getRatingCode();
            }
        }
    }

    /**
     * Apply filter to collection
     *
     * @param AbstractCollection $collection
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    protected function _prepareEntityCollection(AbstractCollection $collection)
    {
        if (!empty($this->_parameters[Processor::LAST_ENTITY_ID]) &&
            $this->_parameters[Processor::LAST_ENTITY_SWITCH] > 0
        ) {
            $collection->addFieldToFilter(
                'main_table.review_id',
                ['gt' => $this->_parameters[Processor::LAST_ENTITY_ID]]
            );
        }
        $nameAttributeId = $this->getAttributeIdofProductName();
        $productEntityField = $this->getProductEntityLinkField();
        $collection->getSelect()
            ->joinLeft(
                ['cpev' => $this->_resourceModel
                    ->getTableName('catalog_product_entity_varchar')
                ],
                "main_table.entity_pk_value = cpev." . $productEntityField,
                ["product_name" => "value"]
            )
            ->where("cpev.store_id = " . Store::DEFAULT_STORE_ID)
            ->where(
                "cpev.attribute_id = " . $nameAttributeId
            );

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
        foreach ($columns[$this->getEntityTypeCode()] as $field) {
            $fields[$field['field']] = $field['type'];
        }

        foreach ($filters as $key => $value) {
            if (isset($fields[$key])) {
                $type = $fields[$key];

                if ($key == 'review_id') {
                    $key = 'main_table.review_id';
                }
                if ($key == 'created_at') {
                    $key = 'main_table.created_at';
                }
                if ($key == 'status') {
                    $key = 'rs.status_code';
                    $collection->getSelect()->join(
                        ['rs' => 'review_status'],
                        'main_table.status_id = rs.status_id',
                        ['rs.status_code']
                    );
                }
                if ($key == 'product_name') {
                    $key = 'cpev.value';
                }
                if (in_array($key, $this->ratingFields)) {
                    $ratingId = array_search($key, $this->ratingFields);
                    $reviewIds = $this->getReviewIds($ratingId, $value);
                    $collection->addFieldToFilter(
                        'main_table.review_id',
                        ['in' => $reviewIds]
                    );
                    continue;
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
     * @param $ratingId
     * @param $value
     * @return array|bool
     */
    protected function getReviewIds($ratingId, $value)
    {
        $rovTable = $this->_resourceModel->getTableName('rating_option_vote');
        $reviewTable = $this->_resourceModel->getTableName('review');
        $select = $this->_resourceModel->getConnection()->select();
        $select->from(['r' => $reviewTable], 'r.review_id')
            ->join(
                ['rov' => $rovTable],
                'rov.review_id = r.review_id',
                []
            )
            ->where('rov.rating_id = ?', $ratingId)
            ->where('rov.value IN (?) ', $value);

        $result = $this->_resourceModel->getConnection()->fetchAll($select);
        return $result ? $result : false;
    }

    /**
     * Retrieve entity collection
     *
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    protected function _getEntityCollection()
    {
        if (null === $this->entityCollection) {
            $this->entityCollection = $this->collectionFactory->create(
                ReviewCollection::class
            );
        }
        return $this->entityCollection;
    }

    /**
     * Retrieve status map
     *
     * @return array
     */
    protected function getStatusMap()
    {
        if (null === $this->statusMap) {
            $collection = $this->collectionFactory->create(
                StatusCollection::class
            );
            /** @var \Magento\Review\Model\Review\Status $status */
            foreach ($collection as $status) {
                $this->statusMap[$status->getId()] = $status->getStatusCode();
            }
        }
        return $this->statusMap;
    }

    /**
     * Retrieve entity field columns
     *
     * @return array
     */
    public function getFieldColumns()
    {
        $options = [];
        foreach ($this->getFieldsForExport() as $field) {
            $select = [];
            $type = 'text';
            if ($field == 'review_id') {
                $type = 'int';
            }
            if (in_array($field, $this->ratingFields)) {
                $type = 'int';
            }
            if ($field == 'created_at') {
                $type = 'date';
            }
            if ($field == 'status') {
                $type = 'select';
                $select[] = ['label' => __('Approved'), 'value' => 'Approved'];
                $select[] = ['label' => __('Pending'), 'value' => 'Pending'];
                $select[] = ['label' => __('Not Approved'), 'value' => 'Not Approved'];
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
     * Retrieve entity field for filter
     *
     * @return array
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
     */
    public function getFieldsForExport()
    {
        return array_merge(
            $this->exportFields,
            $this->ratingFields
        );
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
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getAttributeIdofProductName()
    {
        $productNameAttributeId = $this->attributeRepository->get(
            'catalog_product',
            'name'
        )->getId();

        return $productNameAttributeId;
    }

    /**
     * Get product entity Link Field
     *
     * @return string
     */
    protected function getProductEntityLinkField()
    {
        if (!$this->productEntityLinkField) {
            $this->productEntityLinkField = $this->metadataPool
                ->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class)
                ->getLinkField();
        }
        return $this->productEntityLinkField;
    }
}
