<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import;

use Firebear\ImportExport\Helper\Additional;
use Firebear\ImportExport\Traits\Import\Entity as ImportTrait;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Cms\Model\BlockFactory as CmsBlockFactory;
use Magento\Cms\Model\ResourceModel\Block\CollectionFactory;
use Magento\Cms\Model\ResourceModel\BlockFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\StringUtils;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\ResourceModel\Helper;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Model\ResourceModel\Theme\Collection;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class CmsBlock
 *
 * @package Firebear\ImportExport\Model\Import
 */
class CmsBlock extends AbstractEntity
{
    use ImportTrait;

    /**
     * Column category url key.
     */
    const COL_URL = 'identifier';

    /**
     * Column cms block_id.
     */
    const COL_BLOCKID = 'block_id';

    /**
     * Column cms store_view_code.
     */
    const COL_STORE_VIEW_CODE = 'store_view_code';

    /**
     * Core event manager proxy
     *
     * @var ManagerInterface
     */
    protected $eventManager = null;

    /**
     * Flag for replace operation.
     *
     * @var null
     */
    protected $replaceFlag = null;
    protected $_validatedRows = [];
    protected $_processedEntitiesCount = null;

    protected $collectionFactory;
    protected $blockFactory;
    protected $blockRepositoryInterface;

    protected $storeManager;

    protected $resource;
    protected $blocksUrl;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * Block title to ID hash.
     *
     * @var array
     */
    protected $blocks = [];

    protected $sourceType;
    protected $blockResourceFactory;
    protected $additional;

    protected $blockFields = [
        BlockInterface::BLOCK_ID,
        BlockInterface::CONTENT,
        BlockInterface::CREATION_TIME,
        BlockInterface::IDENTIFIER,
        BlockInterface::IS_ACTIVE,
        BlockInterface::TITLE,
        BlockInterface::UPDATE_TIME
    ];
    /**
     * @var Collection
     */
    protected $themeCollection;

    /**
     * @var Store
     */
    protected $store;

    /**
     * CmsBlock constructor.
     * @param Data $jsonHelper
     * @param \Magento\ImportExport\Helper\Data $importExportData
     * @param \Magento\ImportExport\Model\ResourceModel\Import\Data $importData
     * @param Config $config
     * @param ResourceConnection $resource
     * @param Helper $resourceHelper
     * @param StringUtils $string
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param CollectionFactory $collectionFactory
     * @param CmsBlockFactory $blockFactory
     * @param ManagerInterface $eventManager
     * @param StoreManagerInterface $storeManager
     * @param BlockRepositoryInterface $blockRepositoryInterface
     * @param ConsoleOutput $output
     * @param Registry $registry
     * @param \Firebear\ImportExport\Model\ResourceModel\Import\Data $importFireData
     * @param BlockFactory $blockResourceFactory
     * @param Additional $additional
     * @param Collection $themeCollection
     * @param Store $store
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        Data $jsonHelper,
        \Magento\ImportExport\Helper\Data $importExportData,
        \Magento\ImportExport\Model\ResourceModel\Import\Data $importData,
        Config $config,
        ResourceConnection $resource,
        Helper $resourceHelper,
        StringUtils $string,
        ProcessingErrorAggregatorInterface $errorAggregator,
        CollectionFactory $collectionFactory,
        CmsBlockFactory $blockFactory,
        ManagerInterface $eventManager,
        StoreManagerInterface $storeManager,
        BlockRepositoryInterface $blockRepositoryInterface,
        ConsoleOutput $output,
        Registry $registry,
        \Firebear\ImportExport\Model\ResourceModel\Import\Data $importFireData,
        \Magento\Cms\Model\ResourceModel\BlockFactory $blockResourceFactory,
        Additional $additional,
        Collection $themeCollection,
        Store $store
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->blockFactory = $blockFactory;
        $this->blockRepositoryInterface = $blockRepositoryInterface;
        $this->storeManager = $storeManager;
        $this->eventManager = $eventManager;
        $this->registry = $registry;
        $this->output = $output;
        $this->blockResourceFactory = $blockResourceFactory;
        $this->additional = $additional;
        parent::__construct(
            $jsonHelper,
            $importExportData,
            $importData,
            $config,
            $resource,
            $resourceHelper,
            $string,
            $errorAggregator
        );
        $this->_dataSourceModel = $importFireData;
        $this->blocksUrl = [];
        $this->themeCollection = $themeCollection;
        $this->store = $store;
        $this->initCMSBlocks();
    }

    /**
     *
     */
    protected function initCMSBlocks()
    {
        if (empty($this->blocks)) {
            $stores = $this->storeManager->getStores();
            $searchStores = [\Magento\Store\Model\Store::DEFAULT_STORE_ID];
            foreach ($stores as $store) {
                $searchStores[] = $store->getId();
            }
            foreach ($searchStores as $store) {
                $collection = $this->collectionFactory->create();
                $collection->addStoreFilter($store);

                /** @var \Magento\Cms\Model\Block $block */
                foreach ($collection as $block) {
                    $this->blocks[$block->getIdentifier()] = $block->getId();
                }
            }
        }
    }

    public function getAllFields()
    {
        return array_unique(
            $this->blockFields
        );
    }

    /**
     * Import data rows.
     *
     * @return boolean
     */
    protected function _importData()
    {
        $this->_validatedRows = null;
        if (Import::BEHAVIOR_DELETE == $this->getBehavior()) {
            $this->deletePages();
        } else {
            /**
             * If user select replace behavior all categories will be deleted first,
             * then new categories will be saved
             */
            $this->savePagesData();
        }
        $this->eventManager->dispatch('cms_blocks_import_finish_before', ['adapter' => $this]);

        return true;
    }

    /**
     * Delete pages is delete behaviour is selected
     *
     * @return $this
     */
    protected function deletePages()
    {
        $blockId = null;
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            foreach ($bunch as $rowNum => $rowData) {
                if (!$this->validateRow($rowData, $rowNum)) {
                    continue;
                }
                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                    continue;
                }
                if (isset($rowData[self::COL_URL]) && isset($this->blocks[$rowData[self::COL_URL]])) {
                    $blockId = (int)$this->blocks[$rowData[self::COL_URL]];
                } elseif (isset($rowData[self::COL_BLOCKID])) {
                    $blockId = (int)$rowData[self::COL_BLOCKID];
                }

                if ($blockId) {
                    if ($this->blockFactory->create()->
                    getCollection()->addFieldToFilter(self::COL_BLOCKID, $blockId)
                        ->getSize()) {
                        try {
                            $block = $this->blockRepositoryInterface->getById($blockId);
                            $this->blockRepositoryInterface->delete($block);
                        } catch (\Exception $e) {
                            $this->addRowError(
                                $e->getMessage(),
                                $rowNum
                            );
                        }
                    }
                } else {
                    $this->addRowError(
                        'Cannot delete pages ',
                        $rowNum
                    );
                }
            }
        }
        return $this;
    }

    /**
     * Validate data row.
     *
     * @param array $rowData
     * @param int $rowNum
     * @return boolean
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function validateRow(array $rowData, $rowNum)
    {

        if (isset($this->_validatedRows[$rowNum])) {
            // check that row is already validated
            return !$this->getErrorAggregator()->isRowInvalid($rowNum);
        }

        $this->_validatedRows[$rowNum] = true;
        $this->_processedEntitiesCount++;

        return !$this->getErrorAggregator()->isRowInvalid($rowNum);
    }

    /**
     * Gather and save information of cms pages entities
     *
     * @return CmsBlock
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function savePagesData()
    {
        if (Import::BEHAVIOR_REPLACE == $this->getBehavior()) {
            $this->deletePages();
        }

        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            foreach ($bunch as $rowNum => $rowData) {
                $rowData = $this->joinIdenticalyData($rowData);
                $rowData = $this->customChangeData($rowData);
                if (!$this->validateRow($rowData, $rowNum)) {
                    $this->addLogWriteln(
                        __('block with name: %1 is not valided', $rowData['title']),
                        $this->output,
                        'info'
                    );
                    continue;
                }
                $time = explode(" ", microtime());
                $startTime = $time[0] + $time[1];
                $name = $rowData['title'];

                if (!$this->validateRow($rowData, $rowNum)) {
                    continue;
                }

                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                    continue;
                }

                if (Import::BEHAVIOR_REPLACE == $this->getBehavior()) {
                    if (isset($rowData['block_id'])) {
                        unset($rowData['block_id']);
                    }
                }

                $rowData = $this->checkUrl($rowData);
                $blockStores = $this->getStore($rowData);

                if ($rowData) {
                    try {
                        $block = $this->blockFactory->create();
                        $block->setData($rowData);
                        $block->setStoreId($blockStores);
                        $block->save();
                    } catch (\Exception $e) {
                        $this->getErrorAggregator()->addError(
                            $e->getCode(),
                            ProcessingError::ERROR_LEVEL_NOT_CRITICAL,
                            $this->_processedRowsCount,
                            null,
                            $e->getMessage()
                        );
                        $this->_processedRowsCount++;
                    }
                }
                $time = explode(" ", microtime());
                $endTime = $time[0] + $time[1];
                $totalTime = $endTime - $startTime;
                $totalTime = round($totalTime, 5);
                $this->addLogWriteln(__('block with name: %1 .... %2s', $name, $totalTime), $this->output, 'info');
            }
            $this->eventManager->dispatch(
                'cms_blocks_bunch_save_after',
                ['adapter' => $this, 'bunch' => $bunch]
            );
        }
        return $this;
    }

    /**
     * @param $rowData
     * @return mixed
     */
    protected function checkUrl($rowData)
    {
        if (isset($rowData[self::COL_URL])) {
            $url = $this->searchUrl($rowData[self::COL_URL]);
            $rowData[self::COL_URL] = $url;
            $this->blocksUrl[] = $url;
        }

        return $rowData;
    }

    /**
     * @param $url
     * @return mixed
     */
    protected function searchUrl($url)
    {
        if (in_array($url, $this->blocksUrl)) {
            preg_match_all("/\d+$/i", $url, $out);
            if (isset($out[0][0])) {
                $counter = (int)$out[0][0];
                $url = $this->searchUrl(str_replace($counter, ++$counter, $url));
            } else {
                $url = $url;
            }
        }

        if ($this->checkUrlKeyDuplicates($url)) {
            preg_match_all("/\d+$/i", $url, $out);
            if (isset($out[0][0])) {
                $counter = (int)$out[0][0];
                $url = $this->searchUrl(str_replace($counter, ++$counter, $url));
            }
        }
        return $url;
    }

    protected function checkUrlKeyDuplicates($urlKeys)
    {
        $resource = $this->getResource();
        $select = $this->_connection->select()->from(
            ['url_rewrite' => $resource->getTable('url_rewrite')],
            ['request_path', 'store_id']
        )->joinLeft(
            ['cpe' => $resource->getTable('cms_block')],
            "cpe.block_id = url_rewrite.entity_id"
        )->where('request_path LIKE "%' . $urlKeys . '%"');
        $urlKeyDuplicates = $this->_connection->fetchAssoc(
            $select
        );

        return count($urlKeyDuplicates);
    }

    protected function getResource()
    {
        if (!$this->resource) {
            $this->resource = $this->blockResourceFactory->create();
        }
        return $this->resource;
    }

    protected function getStore($rowData)
    {
        $storeIds = [];
        if (isset($rowData[self::COL_STORE_VIEW_CODE])) {
            if ($rowData[self::COL_STORE_VIEW_CODE] != '') {
                foreach (explode(',', $rowData[self::COL_STORE_VIEW_CODE]) as $_storeCode) {
                    if ($_storeCode === 'All') {
                        array_push($storeIds, 0);
                    } else {
                        array_push($storeIds, $this->store->load($_storeCode)->getId());
                    }
                }
            } else {
                array_push($storeIds, 0);
            }
        }
        return $storeIds;
    }

    /**
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
        $maxDataSize = $this->_resourceHelper->getMaxDataSize();
        $bunchSize = $this->_importExportData->getBunchSize();

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
                $currentDataSize = strlen($this->phpSerialize($bunchRows));
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

                $this->_processedRowsCount++;
                $rowData = $this->customBunchesData($rowData);
                $rowSize = strlen($this->jsonHelper->jsonEncode($rowData));

                $isBunchSizeExceeded = $bunchSize > 0 && count($bunchRows) >= $bunchSize;

                if ($currentDataSize + $rowSize >= $maxDataSize || $isBunchSizeExceeded) {
                    $startNewBunch = true;
                    $nextRowBackup = [$source->key() => $rowData];
                } else {
                    $bunchRows[$source->key()] = $rowData;
                    $currentDataSize += $rowSize;
                }

                $source->next();
            }
        }
        return $this;
    }

    /**
     * EAV entity type code getter.
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        return 'cms_block';
    }
}
