<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Import;

use Firebear\ImportExport\Traits\Import\Entity as ImportTrait;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Cms\Model\BlockFactory;
use Magento\Cms\Model\ResourceModel\Block as BlockResource;
use Magento\Cms\Model\PageFactory;
use Magento\Cms\Model\ResourceModel\Page as PageResource;
use Magento\Store\Model\Store;
use Magento\Theme\Model\ResourceModel\Theme\CollectionFactory as ThemeCollectionFactory;
use Magento\Widget\Model\Widget\InstanceFactory as WidgetFactory;
use Magento\Widget\Model\Config\Data as DataStorage;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\AbstractEntity;
use Magento\ImportExport\Model\ImportFactory;

/**
 * Widget import
 */
class Widget extends AbstractEntity implements ImportAdapterInterface
{
    use ImportTrait;

    /**
     * Widget id column name
     */
    const COLUMN_WIDGET_ID = 'widget_id';

    /**
     * Widget factory
     *
     * @var \Magento\Widget\Model\Widget\InstanceFactory
     */
    protected $widgetFactory;

    /**
     * Source model
     *
     * @var \Magento\ImportExport\Model\ResourceModel\Helper
     */
    protected $resourceHelper;

    /**
     * Import export data
     *
     * @var \Magento\ImportExport\Helper\Data
     */
    protected $importExportData;

    /**
     * Field list
     *
     * @var array
     */
    protected $fields = [
        'widget_id',
        'theme',
        'title',
        'type_code'
    ];

    /**
     * Error codes
     */
    const ERROR_WIDGET_ID_IS_EMPTY = 'widgetIdIsEmpty';

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_messageTemplates = [
        self::ERROR_WIDGET_ID_IS_EMPTY => 'Widget id is empty',
    ];

    /**
     * Widget config
     *
     * @var DataStorage
     */
    protected $widgetConfig;

    /**
     * Block factory
     *
     * @var BlockFactory
     */
    protected $blockFactory;

    /**
     * Block resource
     *
     * @var BlockResource
     */
    protected $blockResource;

    /**
     * Page factory
     *
     * @var PageFactory
     */
    protected $pageFactory;

    /**
     * Page resource
     *
     * @var PageResource
     */
    protected $pageResource;

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
        'layout:block' => 'block',
        'layout:entities' => 'entities',
        'layout:template' => 'template',
        'layout:for' => 'for'
    ];

    /**
     * Last widget id
     *
     * @var int|null
     */
    protected $lastWidgetId;

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
     * Initialize import
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param ImportFactory $importFactory
     * @param WidgetFactory $widgetFactory
     * @param DataStorage $dataStorage
     * @param BlockFactory $blockFactory
     * @param BlockResource $blockResource
     * @param PageFactory $pageFactory
     * @param PageResource $pageResource
     * @param ThemeCollectionFactory $themeCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        ImportFactory $importFactory,
        WidgetFactory $widgetFactory,
        DataStorage $dataStorage,
        BlockFactory $blockFactory,
        BlockResource $blockResource,
        PageFactory $pageFactory,
        PageResource $pageResource,
        ThemeCollectionFactory $themeCollectionFactory,
        array $data = []
    ) {
        $this->_logger = $context->getLogger();
        $this->output = $context->getOutput();
        $this->importExportData = $context->getImportExportData();
        $this->resourceHelper = $context->getResourceHelper();
        $this->jsonHelper = $context->getJsonHelper();
        $this->widgetFactory = $widgetFactory;
        $this->widgetConfig = $dataStorage->get();
        $this->blockFactory = $blockFactory;
        $this->blockResource = $blockResource;
        $this->pageFactory = $pageFactory;
        $this->pageResource = $pageResource;
        $this->themeCollectionFactory = $themeCollectionFactory;

        parent::__construct(
            $context->getStringUtils(),
            $scopeConfig,
            $importFactory,
            $context->getResourceHelper(),
            $context->getResource(),
            $context->getErrorAggregator(),
            $data
        );
    }

    /**
     * Import data rows
     *
     * @return boolean
     */
    protected function _importData()
    {
        $this->initThemeList();
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            foreach ($bunch as $rowNumber => $rowData) {
                /* validate data */
                if (!$rowData || !$this->validateRow($rowData, $rowNumber)) {
                    continue;
                }

                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNumber);
                    continue;
                }

                /* behavior selector */
                switch ($this->getBehavior()) {
                    case Import::BEHAVIOR_DELETE:
                        $this->delete($rowData);
                        break;
                    case Import::BEHAVIOR_REPLACE:
                        $this->delete($rowData);
                        $this->save($rowData);
                        break;
                    case Import::BEHAVIOR_ADD_UPDATE:
                        $this->save($rowData);
                        break;
                }
            }
        }
        return true;
    }

    /**
     * Imported entity type code getter
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        return 'widget';
    }

    /**
     * Retrieve All Fields Source
     *
     * @return array
     */
    public function getAllFields()
    {
        return $this->fields;
    }

    /**
     * Validate data row
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return bool
     */
    public function validateRow(array $rowData, $rowNumber)
    {
        if (isset($this->_validatedRows[$rowNumber])) {
            /* check that row is already validated */
            return !$this->getErrorAggregator()->isRowInvalid($rowNumber);
        }

        $this->_validatedRows[$rowNumber] = true;
        $this->_processedEntitiesCount++;

        /* behavior selector */
        switch ($this->getBehavior()) {
            case Import::BEHAVIOR_DELETE:
                $this->validateRowForDelete($rowData, $rowNumber);
                break;
            case Import::BEHAVIOR_REPLACE:
                $this->validateRowForReplace($rowData, $rowNumber);
                break;
            case Import::BEHAVIOR_ADD_UPDATE:
                $this->validateRowForUpdate($rowData, $rowNumber);
                break;
        }
        return !$this->getErrorAggregator()->isRowInvalid($rowNumber);
    }

    /**
     * Validate row data for replace behaviour
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return void
     */
    public function validateRowForReplace(array $rowData, $rowNumber)
    {
        $this->validateRowForDelete($rowData, $rowNumber);
        $this->validateRowForUpdate($rowData, $rowNumber);
    }

    /**
     * Validate row data for delete behaviour
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return void
     */
    public function validateRowForDelete(array $rowData, $rowNumber)
    {
        if (empty($rowData[self::COLUMN_WIDGET_ID])) {
            $this->addRowError(self::ERROR_WIDGET_ID_IS_EMPTY, $rowNumber);
        }
    }

    /**
     * Validate row data for update behaviour
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return void
     */
    public function validateRowForUpdate(array $rowData, $rowNumber)
    {
        return true;
    }

    /**
     * Delete row
     *
     * @param array $rowData
     * @return $this
     */
    protected function delete(array $rowData)
    {
        $widget = $this->widgetFactory->create();
        $widget->load($rowData[self::COLUMN_WIDGET_ID]);

        if ($widget->getId()) {
            $widget->delete();
            $this->countItemsDeleted++;
        }
        return $this;
    }

    /**
     * Update entity
     *
     * @param array $rowData
     * @return $this
     */
    protected function save(array $rowData)
    {
        $onlyPage = false;
        $widget = $this->widgetFactory->create();
        if (!empty($rowData[self::COLUMN_WIDGET_ID])) {
            $widget->load($rowData[self::COLUMN_WIDGET_ID]);
        }

        $storeIds = explode(',', ($rowData['store_ids'] ?? Store::DEFAULT_STORE_ID));

        if ($widget->getId()) {
            if (!isset($rowData['store_ids'])) {
                $storeIds = $widget->getStoreIds();
            }
            $this->countItemsUpdated++;
        } else {
            $this->countItemsCreated++;
            $rowData[self::COLUMN_WIDGET_ID] = null;
        }

        if (!empty($rowData['type_code'])) {
            $type = $widget->getWidgetReference('code', $rowData['type_code'], 'type');
            $widget->setType($type)
                ->setCode($rowData['type_code']);
        } else {
            if ($this->lastWidgetId) {
                $widget->load($this->lastWidgetId);
            }
            if (!$widget->getId()) {
                return $this;
            } else {
                $onlyPage = true;
            }
        }

        $groupData = [];
        $parameters = $this->getOptionCodes($widget->getCode());
        foreach ($rowData as $column => $value) {
            if (0 === strpos($column, $this->prefix)) {
                $column = str_replace($this->prefix, '', $column);
                if (isset($parameters[$column])) {
                    $parameters[$column] = $value;
                }
            } elseif (0 === strpos($column, 'layout:')) {
                if (isset($this->layoutMap[$column])) {
                    $groupData[$this->layoutMap[$column]] = $value;
                }
            }
        }

        if ('anchor_categories' == $groupData['page_group']) {
            $groupData['is_anchor_only'] = '1';
        }

        if (empty($parameters['template'])) {
            unset($parameters['template']);
        }

        if (isset($parameters['block_id'])) {
            $parameters['block_id'] = ($parameters['block_id'] === '')
                ? null
                : $this->getBlockId($parameters['block_id']);
        }

        if (isset($parameters['page_id'])) {
            $parameters['page_id'] = ($parameters['page_id'] === '')
                ? ''
                : $this->getPageId($parameters['page_id']);
        }

        if (!$onlyPage) {
            $themeId = $this->themeMap['Magento Luma'];
            if (!empty($rowData['theme']) && isset($this->themeMap[$rowData['theme']])) {
                $themeId = $this->themeMap[$rowData['theme']];
            }
            $widget->setTitle($rowData['title'])
                ->setStoreIds($storeIds)
                ->setThemeId($themeId)
                ->setWidgetParameters($parameters)
                ->setSortOrder($rowData['sort_order']);
        }

        $pageGroups = $widget->getPageGroups() ?: [];
        array_push($pageGroups, $groupData);

        $formatPageGroups = [];
        foreach ($pageGroups as $pageGroup) {
            if (isset($pageGroup['page_for'])) {
                $pageGroup['for'] = $pageGroup['page_for'];
            }
            if (isset($pageGroup['block_reference'])) {
                $pageGroup['block'] = $pageGroup['block_reference'];
            }
            if (isset($pageGroup['page_template'])) {
                $pageGroup['template'] = $pageGroup['page_template'];
            }

            unset(
                $pageGroup['page_for'],
                $pageGroup['block_reference'],
                $pageGroup['page_template']
            );

            if (!isset($pageGroup['page_id'])) {
                $pageGroup['page_id'] = 0;
            }

            if (empty($pageGroup['page_group'])) {
                continue;
            }

            $formatPageGroups[$pageGroup['page_id']] = [
                'page_group' => $pageGroup['page_group'],
                $pageGroup['page_group'] => $pageGroup
            ];
        }
        $widget->setPageGroups($formatPageGroups);

        try {
            $widget->save();
            $this->lastWidgetId = $widget->getId();
        } catch (\Exception $exception) {
            $this->addLogWriteln($exception->getMessage(), $this->getOutput(), 'error');
            $this->lastWidgetId = null;

        }
        return $this;
    }

    /**
     * Inner source object getter
     *
     * @return \Magento\ImportExport\Model\Import\AbstractSource
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getSource()
    {
        if (!$this->_source) {
            throw new LocalizedException(__('Please specify a source.'));
        }
        return $this->_source;
    }

    /**
     * Save Validated Bunches
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _saveValidatedBunches()
    {
        $source = $this->_getSource();
        $currentDataSize = 0;
        $bunchRows = [];
        $startNewBunch = false;
        $nextRowBackup = [];
        $maxDataSize = $this->resourceHelper->getMaxDataSize();
        $bunchSize = $this->importExportData->getBunchSize();

        $source->rewind();
        $this->_dataSourceModel->cleanBunches();
        $file = null;
        $jobId = null;
        if (isset($this->_parameters['file'])) {
            $file = $this->_parameters['file'];
        }
        if (isset($this->_parameters['job_id'])) {
            $jobId = $this->_parameters['job_id'];
        }

        while ($source->valid() || $bunchRows) {
            if ($startNewBunch || !$source->valid()) {
                $this->_dataSourceModel->saveBunches(
                    $this->getEntityTypeCode(),
                    $this->getBehavior(),
                    $jobId,
                    $file,
                    $bunchRows
                );
                $bunchRows = $nextRowBackup;
                $currentDataSize = strlen($this->jsonHelper->jsonEncode($bunchRows));
                $startNewBunch = false;
                $nextRowBackup = [];
            }

            if ($source->valid()) {
                try {
                    $rowData = $source->current();
                } catch (\InvalidArgumentException $e) {
                    $this->addRowError($e->getMessage(), $this->_processedRowsCount);
                    $this->_processedRowsCount++;
                    $source->next();
                    continue;
                }
                $rowData = $this->customBunchesData($rowData);
                $this->_processedRowsCount++;
                if ($this->validateRow($rowData, $source->key())) {
                    $rowSize = strlen($this->jsonHelper->jsonEncode($rowData));

                    $isBunchSizeExceeded = $bunchSize > 0 && count($bunchRows) >= $bunchSize;

                    if ($currentDataSize + $rowSize >= $maxDataSize || $isBunchSizeExceeded) {
                            $startNewBunch = true;
                            $nextRowBackup = [$source->key() => $rowData];
                    } else {
                            $bunchRows[$source->key()] = $rowData;
                            $currentDataSize += $rowSize;
                    }
                }
                $source->next();
            }
        }
        return $this;
    }

    /**
     * Retrieve option codes
     *
     * @param string $code
     * @return array
     */
    protected function getOptionCodes($code)
    {
        foreach ($this->widgetConfig as $key => $widget) {
            if ($key == $code) {
                return $widget['parameters'];
            }
        }
        return [];
    }

    /**
     * Retrieve block id by identifier
     *
     * @param string $identifier
     * @throws LocalizedException
     */
    protected function getBlockId($identifier)
    {
        $block = $this->blockFactory->create();
        $this->blockResource->load($block, $identifier, 'identifier');
        return $block->getId() ?: null;
    }

    /**
     * Retrieve page id by identifier
     *
     * @param string $identifier
     * @throws LocalizedException
     */
    protected function getPageId($identifier)
    {
        $page = $this->pageFactory->create();
        $this->pageResource->load($page, $identifier, 'identifier');
        return $page->getId() ?: '';
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
            $this->themeMap[$theme->getThemeTitle()] = $theme->getId();
        }
    }
}
