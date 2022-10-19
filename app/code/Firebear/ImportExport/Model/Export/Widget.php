<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export;

use DateTime;
use Firebear\ImportExport\Helper\Data as Helper;
use Firebear\ImportExport\Model\ExportJob\Processor;
use Firebear\ImportExport\Traits\Export\Entity as ExportTrait;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\ImportExport\Model\Export\AbstractEntity;
use Magento\ImportExport\Model\Export\Factory as CollectionFactory;
use Magento\ImportExport\Model\ResourceModel\CollectionByPagesIteratorFactory;
use Magento\Cms\Model\BlockRepository;
use Magento\Cms\Model\PageRepository;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Model\ResourceModel\Theme\CollectionFactory as ThemeCollectionFactory;
use Magento\Widget\Model\Config\Data as DataStorage;
use Magento\Widget\Model\ResourceModel\Widget\Instance\Collection as WidgetCollection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Widget export
 */
class Widget extends AbstractEntity implements EntityInterface
{
    use ExportTrait;

    /**
     * Widget id column name
     */
    const COLUMN_WIDGET_ID = 'widget_id';

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
     * Helper
     *
     * @var Helper
     */
    protected $_helper;

    /**
     * Field list
     *
     * @var array
     */
    protected $fields = [
        self::COLUMN_WIDGET_ID,
        'type_code',
        'store_ids',
        'theme',
        'sort_order',
        'title',
        'layout:id',
        'layout:page',
        'layout:handle',
        'layout:block',
        'layout:for',
        'layout:entities',
        'layout:template'
    ];

    /**
     * Widget config
     *
     * @var DataStorage
     */
    protected $widgetConfig;

    /**
     * Block repository
     *
     * @var BlockRepository
     */
    protected $blockRepository;

    /**
     * Page repository
     *
     * @var PageRepository
     */
    protected $pageRepository;

    /**
     * Option prefix
     *
     * @var string
     */
    protected $prefix = 'option:';

    /**
     * Layout fields map
     *
     * @var array
     */
    protected $layoutMap = [
        'layout:id' => 'page_id',
        'layout:page' => 'page_group',
        'layout:handle' => 'layout_handle',
        'layout:block' => 'block_reference',
        'layout:entities' => 'entities',
        'layout:template' => 'page_template',
        'layout:for' => 'page_for'
    ];

    /**
     * Theme collection factory
     *
     * @var ThemeCollectionFactory
     */
    protected $themeCollectionFactory;

    /**
     * Theme map
     *
     * @var array
     */
    protected $themeMap = [];

    /**
     * Initialize export
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $collectionFactory
     * @param CollectionByPagesIteratorFactory $resourceColFactory
     * @param DataStorage $dataStorage
     * @param BlockRepository $blockRepository
     * @param PageRepository $pageRepository
     * @param ThemeCollectionFactory $themeCollectionFactory
     * @param LoggerInterface $logger
     * @param ConsoleOutput $output
     * @param Helper $helper
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        CollectionFactory $collectionFactory,
        CollectionByPagesIteratorFactory $resourceColFactory,
        DataStorage $dataStorage,
        BlockRepository $blockRepository,
        PageRepository $pageRepository,
        ThemeCollectionFactory $themeCollectionFactory,
        LoggerInterface $logger,
        ConsoleOutput $output,
        Helper $helper,
        array $data = []
    ) {
        $this->_logger = $logger;
        $this->output = $output;
        $this->_helper = $helper;
        $this->_collectionFactory = $collectionFactory;
        $this->widgetConfig = $dataStorage->get();
        $this->blockRepository = $blockRepository;
        $this->pageRepository = $pageRepository;
        $this->themeCollectionFactory = $themeCollectionFactory;

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

        $this->initThemeList();
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
     * Retrieve entity collection
     *
     * @return AbstractCollection
     */
    protected function _getEntityCollection()
    {
        if (null === $this->_entityCollection) {
            $this->_entityCollection = $this->_collectionFactory->create(
                WidgetCollection::class
            );
        }
        return $this->_entityCollection;
    }

    /**
     * Retrieve merged columns
     *
     * @return array
     */
    protected function getMergedColumns()
    {
        $fields = [];
        foreach ($this->getOptionCodes() as $option) {
            $fields[] = $this->prefix . $option;
        }
        return array_merge($this->fields, $fields);
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
                'main_table.instance_id',
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
        foreach ($columns['widget'] as $field) {
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
        return 'widget';
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
            $type = $this->_helper->convertTypesTables($field['DATA_TYPE']);
            $options[$this->getEntityTypeCode()][] = [
                'field' => $key,
                'type' => $type,
                'select' => []
            ];
        }
        return $options;
    }

    /**
     * Retrieve the column descriptions for a table, include additional table
     *
     * @return array
     * @throws LocalizedException
     */
    protected function describeTable()
    {
        $resource = $this->_getEntityCollection()->getResource();
        $fields = $resource->getConnection()->describeTable($resource->getMainTable());
        return $fields;
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
        $this->lastEntityId = $item->getId();
        $item->load($item->getId());

        $pageGroups = $item->getPageGroups() ?: [];
        $rowCount = max(1, count($pageGroups));
        $firstRow = $rowCount;

        while (0 < $rowCount) {
            $data = array_fill_keys($this->getMergedColumns(), '');
            $pageGroup = array_shift($pageGroups);

            if ($rowCount === $firstRow) {
                $data[self::COLUMN_WIDGET_ID] = $item->getId();
                $data['type_code'] = $item->getCode();
                $data['store_ids'] = implode(',', $item->getStoreIds());
                $data['theme'] = $this->themeMap[$item->getThemeId()];
                $data['title'] = $item->getTitle();
                $data['sort_order'] = $item->getSortOrder();

                foreach ($item->getWidgetParameters() as $option => $value) {
                    $data[$this->prefix . $option] = $value;
                }

                $blockId = $data[$this->prefix . 'block_id'];
                if (0 < $blockId) {
                    $block = $this->blockRepository->getById($blockId);
                    $data[$this->prefix . 'block_id'] = $block->getIdentifier();
                }

                $pageId = $data[$this->prefix . 'page_id'];
                if (0 < $pageId) {
                    $page = $this->pageRepository->getById($pageId);
                    $data[$this->prefix . 'page_id'] = $page->getIdentifier();
                }
            }

            foreach ($this->layoutMap as $column => $key) {
                $data[$column] = $pageGroup[$key] ?? '';
            }

            $row = $this->changeRow($data);
            $this->getWriter()->writeRow($row);
            $rowCount--;
        }
        $this->_processedEntitiesCount++;
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
        return array_keys($this->describeTable());
    }

    /**
     * Retrieve header columns
     *
     * @return array
     */
    protected function _getHeaderColumns()
    {
        return $this->changeHeaders($this->getMergedColumns());
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
     * Retrieve option codes
     *
     * @return array
     */
    protected function getOptionCodes()
    {
        $option = [];
        foreach ($this->widgetConfig as $code => $widget) {
            $option = array_merge($option, array_keys($widget['parameters'] ?? []));
        }
        return array_unique($option);
    }

    /**
     * Init themes list
     *
     * @return void
     */
    protected function initThemeList()
    {
        $collection = $this->themeCollectionFactory->create();
        foreach ($collection as $theme) {
            $this->themeMap[$theme->getId()] = $theme->getThemeTitle();
        }
    }
}
