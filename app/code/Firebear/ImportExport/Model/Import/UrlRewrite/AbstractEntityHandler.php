<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\UrlRewrite;

use Magento\ImportExport\Model\Import\AbstractEntity;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;

/**
 * Abstract entity handler
 */
class AbstractEntityHandler
{
    const ENTITY_TYPE = '';

    /**
     * Import entity adapter
     *
     * @var AbstractEntity
     */
    protected $_importEntity;

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_messageTemplates = [];

    /**
     * Initialize handler
     *
     * @param AbstractEntity $importEntity
     * @return $this
     */
    public function init(AbstractEntity $importEntity)
    {
        $this->_importEntity = $importEntity;
        foreach ($this->_messageTemplates as $errorCode => $message) {
            $this->getImportEntity()->addMessageTemplate($errorCode, $message);
        }
        return $this;
    }

    /**
     * Retrieve import entity
     *
     * @return AbstractEntity
     */
    public function getImportEntity()
    {
        return $this->_importEntity;
    }

    /**
     * Retrieve error aggregator
     *
     * @return ProcessingErrorAggregatorInterface
     */
    public function getErrorAggregator()
    {
        return $this->_importEntity->getErrorAggregator();
    }

    /**
     * Add error with corresponding current data source row number
     *
     * @param string $errorCode Error code or simply column name
     * @param int $errorRowNum Row number
     * @param string $colName OPTIONAL Column name
     * @param string $errorMessage OPTIONAL Column name
     * @param string $errorLevel
     * @param string $errorDescription
     * @return AbstractEntity
     */
    public function addRowError(
        $errorCode,
        $errorRowNum,
        $colName = null,
        $errorMessage = null,
        $errorLevel = ProcessingError::ERROR_LEVEL_CRITICAL,
        $errorDescription = null
    ) {
        return $this->_importEntity->addRowError(
            $errorCode,
            $errorRowNum,
            $colName,
            $errorMessage,
            $errorLevel,
            $errorDescription
        );
    }

    /**
     * @return string
     */
    public function getEntityType()
    {
        return static::ENTITY_TYPE;
    }
}
