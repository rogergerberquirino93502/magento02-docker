<?php
/**
 * Custom
 *
 * @copyright Copyright © 2019 Staempfli AG. All rights reserved.
 * @author    juan.alonso@staempfli.com
 */

namespace Firebear\ImportExport\Model\Import\UrlRewrite\EntityHandler;

use Firebear\ImportExport\Model\Import\UrlRewrite\EntityHandler\Common;

class CustomUrlRewrite extends Common
{
    /** @var string  */
    const ENTITY_TYPE = 'custom';

    /**
     * Array of validate attributes
     *
     * @var array
     */
    protected $_validateAttributes = [
        self::COLUMN_TARGET_PATH => self::ERROR_TARGET_PATH_IS_EMPTY,
        self::COLUMN_ENTITY_TYPE => self::ERROR_ENTITY_TYPE_IS_EMPTY,
        self::COLUMN_STORE_ID => self::ERROR_STORE_ID_IS_EMPTY,
        self::COLUMN_REQUEST_PATH => self::ERROR_REQUEST_PATH_IS_EMPTY,
    ];

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
     * Retrieve rewrite id if url is present in database
     *
     * @param array $rowData
     * @return bool|int
     */
    protected function _getExistUrlRewriteId(array $rowData)
    {
        if ($rowData[self::COLUMN_ENTITY_TYPE] == 'custom' && $rowData[self::COLUMN_ENTITY_ID] == '0') {
            return false;
        }

        $bind = [
            ':entity_id' => $rowData[self::COLUMN_ENTITY_ID],
            ':store_id' => $rowData[self::COLUMN_STORE_ID],
            ':entity_type' => $rowData[self::COLUMN_ENTITY_TYPE]
        ];
        /** @var $select \Magento\Framework\DB\Select */
        $select = $this->_connection->select();
        $select->from($this->_resource->getTableName('url_rewrite'), self::COLUMN_URL_REWRITE_ID)
            ->where('entity_id = :entity_id')
            ->where('store_id = :store_id')
            ->where('entity_type = :entity_type');

        return $this->_connection->fetchOne($select, $bind);
    }
}
