<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Import\UrlRewrite\EntityHandler;

use Magento\Cms\Model\ResourceModel\Page as PageResource;
use Firebear\ImportExport\Model\Import\UrlRewrite\AbstractEntityHandler;
use Firebear\ImportExport\Model\Import\UrlRewrite\EntityHandlerInterface;

/**
 * Cms handler
 */
class Cms extends AbstractEntityHandler implements EntityHandlerInterface
{
    /** @var string  */
    const ENTITY_TYPE = 'cms-page';

    /**
     * Entity Id column name
     */
    const COLUMN_ENTITY_ID = 'entity_id';

    /**
     * Entity type column name
     */
    const COLUMN_ENTITY_TYPE = 'entity_type';

    /**
     * identifier Column name
     */
    const COLUMN_IDENTIFIER = 'identifier';

    /**
     * Target path column name
     */
    const COLUMN_TARGET_PATH = 'target_path';

    /**
     * Store id column name
     */
    const COLUMN_STORE_ID = 'store_id';

    /**
     * Url rewrite_id column name
     */
    const COLUMN_URL_REWRITE_ID = 'url_rewrite_id';

    /**
     * Product instance
     *
     * @var \Magento\Cms\Model\ResourceModel\Page
     */
    protected $_pageResource;

    /**
     * Error codes
     */
    const ERROR_IDENTIFIER_NOT_FOUND = 'urlRewriteidentifierNotFound';
    const ERROR_STORE_ID_IS_EMPTY = 'storeIdIsEmpty';
    const ERROR_URL_REWRITE_ID_IS_EMPTY = 'urlRewriteIdIsEmpty';
    const ERROR_CMS_PAGE_NOT_FOUND = 'urlRewriteCmsPageNotFound';

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_messageTemplates = [
        self::ERROR_IDENTIFIER_NOT_FOUND => 'Page with specified identifier not found',
        self::ERROR_STORE_ID_IS_EMPTY => 'Store Id is empty',
        self::ERROR_URL_REWRITE_ID_IS_EMPTY => 'Url rewrite id is empty',
        self::ERROR_CMS_PAGE_NOT_FOUND => 'CMS Page with specified entity id (Page ID) not found'
    ];

    /**
     * Initialize import
     *
     * @param PageResource $pageResource
     */
    public function __construct(
        PageResource $pageResource
    ) {
        $this->_pageResource = $pageResource;
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
        $this->validateRowForUpdate($rowData, $rowNumber);
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
        if (!empty($rowData[self::COLUMN_IDENTIFIER])) {
            if (empty($rowData[self::COLUMN_STORE_ID])) {
                $this->addRowError(self::ERROR_STORE_ID_IS_EMPTY, $rowNumber);
            } else {
                $entityId = $this->_pageResource->checkIdentifier(
                    $rowData[self::COLUMN_IDENTIFIER],
                    $rowData[self::COLUMN_STORE_ID]
                );
                if (!$entityId) {
                    $this->addRowError(self::ERROR_IDENTIFIER_NOT_FOUND, $rowNumber);
                }
            }
        } elseif (!empty($rowData[self::COLUMN_ENTITY_ID])) {
            if (!count($this->_pageResource->lookupStoreIds($rowData[self::COLUMN_ENTITY_ID]))) {
                $this->addRowError(self::ERROR_CMS_PAGE_NOT_FOUND, $rowNumber);
            }
        }
    }

    /**
     * Prepare row data for update behaviour
     *
     * @param array $rowData
     * @return array
     */
    public function prepareRowForUpdate(array $rowData)
    {
        if (!empty($rowData[self::COLUMN_IDENTIFIER])) {
            $entityId = $this->_pageResource->checkIdentifier(
                $rowData[self::COLUMN_IDENTIFIER],
                $rowData[self::COLUMN_STORE_ID]
            );
            if ($entityId) {
                $rowData[self::COLUMN_ENTITY_ID] = $entityId;
            }
            if (empty($rowData[self::COLUMN_ENTITY_TYPE])) {
                $rowData[self::COLUMN_ENTITY_TYPE] = 'cms-page';
            }
        }

        if (!empty($rowData[self::COLUMN_ENTITY_TYPE]) &&
            $rowData[self::COLUMN_ENTITY_TYPE] == 'cms-page'
        ) {
            if (empty($rowData[self::COLUMN_TARGET_PATH]) && !empty($rowData[self::COLUMN_ENTITY_ID])) {
                $rowData[self::COLUMN_TARGET_PATH] = $this->_getCanonicalUrlPath($rowData);
            }
        }
        unset($rowData[self::COLUMN_IDENTIFIER]);
        return $rowData;
    }

    /**
     * Prepare row data for replace behaviour
     *
     * @param array $rowData
     * @return array
     */
    public function prepareRowForReplace(array $rowData)
    {
        return $this->prepareRowForUpdate($rowData);
    }

    /**
     * Prepare row data for delete behaviour
     *
     * @param array $rowData
     * @return array
     */
    public function prepareRowForDelete(array $rowData)
    {
        return $this->prepareRowForUpdate($rowData);
    }

    /**
     * Retrieve canonical page url path
     *
     * @param array $rowData
     * @return string
     */
    protected function _getCanonicalUrlPath(array $rowData)
    {
        return 'cms/page/view/page_id/' . $rowData[self::COLUMN_ENTITY_ID];
    }
}
