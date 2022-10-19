<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Import\UrlRewrite\EntityHandler;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use Firebear\ImportExport\Model\Import\UrlRewrite\AbstractEntityHandler;
use Firebear\ImportExport\Model\Import\UrlRewrite\EntityHandlerInterface;

/**
 * Common handler
 */
class Common extends AbstractEntityHandler implements EntityHandlerInterface
{
    /** @var string  */
    const ENTITY_TYPE = 'common';

    /**
     * Entity Id column name
     */
    const COLUMN_ENTITY_ID = 'entity_id';

    /**
     * Entity type column name
     */
    const COLUMN_ENTITY_TYPE = 'entity_type';

    /**
     * Target path column name
     */
    const COLUMN_TARGET_PATH = 'target_path';

    /**
     * Store id column name
     */
    const COLUMN_STORE_ID = 'store_id';

    /**
     * Request path column name
     */
    const COLUMN_REQUEST_PATH = 'request_path';

    /**
     * Url rewrite_id column name
     */
    const COLUMN_URL_REWRITE_ID = 'url_rewrite_id';

    /**
     * Metadata column name
     */
    const COLUMN_METADATA = 'metadata';

    /**
     * Error codes
     */
    const ERROR_TARGET_PATH_IS_EMPTY = 'targetPathIsEmpty';
    const ERROR_REQUEST_PATH_IS_EMPTY = 'requestPathIsEmpty';
    const ERROR_ENTITY_ID_IS_EMPTY = 'entityIdIsEmpty';
    const ERROR_ENTITY_TYPE_IS_EMPTY = 'entityTypeIsEmpty';
    const ERROR_STORE_ID_IS_EMPTY = 'storeIdIsEmpty';
    const ERROR_JSON_INVALID = 'JsonStringInvalid';

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_messageTemplates = [
        self::ERROR_TARGET_PATH_IS_EMPTY => 'Target path is empty',
        self::ERROR_REQUEST_PATH_IS_EMPTY => 'Request path is empty',
        self::ERROR_ENTITY_ID_IS_EMPTY => 'Entity Id is empty',
        self::ERROR_ENTITY_TYPE_IS_EMPTY => 'Entity type is empty',
        self::ERROR_STORE_ID_IS_EMPTY => 'Store Id is empty',
        self::ERROR_JSON_INVALID => 'Attribute %s has json string is invalid',
    ];

    /**
     * Array of validate attributes
     *
     * @var array
     */
    protected $_validateAttributes = [
        self::COLUMN_ENTITY_ID => self::ERROR_ENTITY_ID_IS_EMPTY,
        self::COLUMN_TARGET_PATH => self::ERROR_TARGET_PATH_IS_EMPTY,
        self::COLUMN_ENTITY_TYPE => self::ERROR_ENTITY_TYPE_IS_EMPTY,
        self::COLUMN_STORE_ID => self::ERROR_STORE_ID_IS_EMPTY,
        self::COLUMN_REQUEST_PATH => self::ERROR_REQUEST_PATH_IS_EMPTY,
    ];

    /**
     * DB connection
     *
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connection;

    /**
     * Resource connection
     *
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;

    /**
     * Serializer
     *
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $serializer;

    /**
     * Initialize Import
     *
     * @param ResourceConnection $resource
     * @param Serializer $serializer
     */
    public function __construct(
        ResourceConnection $resource,
        Serializer $serializer
    ) {
        $this->_resource = $resource;
        $this->_connection = $resource->getConnection();
        $this->serializer = $serializer;
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
        if (empty($rowData[self::COLUMN_STORE_ID])) {
            $this->addRowError(self::ERROR_STORE_ID_IS_EMPTY, $rowNumber);
        }
        if (empty($rowData[self::COLUMN_REQUEST_PATH])) {
            $this->addRowError(self::ERROR_REQUEST_PATH_IS_EMPTY, $rowNumber);
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
        foreach ($this->_validateAttributes as $attribute => $errorCode) {
            if (empty($rowData[$attribute])) {
                if ($attribute == self::COLUMN_ENTITY_ID && $rowData[$attribute] != 0) {
                    $this->addRowError($errorCode, $rowNumber);
                }
            }
        }

        $metadata = $rowData[self::COLUMN_METADATA] ?? null;
        if ($metadata !== null && $metadata !== '') {
            try {
                $this->serializer->unserialize($metadata);
            } catch (\Exception $e) {
                $this->addRowError(self::ERROR_JSON_INVALID, $rowNumber, self::COLUMN_METADATA);
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
        if (empty($rowData[self::COLUMN_URL_REWRITE_ID])) {
            $rowData[self::COLUMN_URL_REWRITE_ID] = $this->_getExistUrlRewriteId($rowData) ?: null;
        }

        $metadata = $rowData[self::COLUMN_METADATA] ?? null;
        if (!$metadata && $metadata !== null) {
            $rowData[self::COLUMN_METADATA] = null;
        }
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
     * Retrieve rewrite id if url is present in database
     *
     * @param array $rowData
     * @return bool|int
     */
    protected function _getExistUrlRewriteId(array $rowData)
    {
        $bind = [
            ':request_path' => $rowData[self::COLUMN_REQUEST_PATH],
            ':store_id' => $rowData[self::COLUMN_STORE_ID],
            ':entity_type' => $rowData[self::COLUMN_ENTITY_TYPE]
        ];
        /** @var $select \Magento\Framework\DB\Select */
        $select = $this->_connection->select();
        $select->from($this->_resource->getTableName('url_rewrite'), self::COLUMN_URL_REWRITE_ID)
            ->where('request_path = :request_path')
            ->where('store_id = :store_id')
            ->where('entity_type = :entity_type')
            ->where('request_path = :request_path');

        return $this->_connection->fetchOne($select, $bind);
    }
}
