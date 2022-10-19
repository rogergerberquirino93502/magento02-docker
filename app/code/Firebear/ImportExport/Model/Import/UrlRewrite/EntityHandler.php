<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\UrlRewrite;

use Firebear\ImportExport\Model\Import\UrlRewrite\EntityHandler\Common as CommonHandler;
use Magento\Framework\Exception\LocalizedException;
use Magento\ImportExport\Model\Import\AbstractEntity;

/**
 * Entity handler
 */
class EntityHandler implements EntityHandlerInterface
{
    /**
     * Entity handlers
     *
     * @var EntityHandlerInterface[]
     */
    protected $_handlers = [];

    /**
     * Initialize handler
     *
     * @param CommonHandler $commonHandler
     * @param EntityHandlerInterface[] $handlers
     */
    public function __construct(
        CommonHandler $commonHandler,
        $handlers = []
    ) {
        $this->_handlers = $handlers;
        $this->_handlers[] = $commonHandler;
    }

    /**
     * Initialize handler
     *
     * @param AbstractEntity $importEntity
     * @return $this
     * @throws LocalizedException
     */
    public function init(AbstractEntity $importEntity)
    {
        foreach ($this->_handlers as $handler) {
            if (!$handler instanceof EntityHandlerInterface) {
                $this->_throwException();
            }
            $handler->init($importEntity);
        }
        return $this;
    }

    /**
     * Throw localized exception
     *
     * @return void
     * @throws LocalizedException
     */
    protected function _throwException()
    {
        throw new LocalizedException(
            __('Adapter must be an instance of %1', EntityHandlerInterface::class)
        );
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
        /** @var EntityHandlerInterface $handler */
        foreach ($this->_handlers as $handler) {
            $handler->validateRowForReplace($rowData, $rowNumber);
        }
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
        /** @var EntityHandlerInterface $handler */
        foreach ($this->_handlers as $handler) {
            if ($this->getValidEntityType($rowData, $handler)) {
                $handler->validateRowForDelete($rowData, $rowNumber);
            }
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
        /** @var EntityHandlerInterface $handler */
        foreach ($this->_handlers as $handler) {
            if ($this->getValidEntityType($rowData, $handler)) {
                $handler->validateRowForUpdate($rowData, $rowNumber);
            }
        }
    }

    /**
     * @param array $rowData
     * @param EntityHandlerInterface $handler
     * @return bool
     */
    private function getValidEntityType(array $rowData, EntityHandlerInterface $handler)
    {
        return isset($rowData[CommonHandler::COLUMN_ENTITY_TYPE])
            && (
            $rowData[CommonHandler::COLUMN_ENTITY_TYPE] === $handler->getEntityType() ||
            $handler->getEntityType() === CommonHandler::ENTITY_TYPE
        );
    }

    /**
     * Prepare row data for update behaviour
     *
     * @param array $rowData
     * @return array
     */
    public function prepareRowForUpdate(array $rowData)
    {
        /** @var EntityHandlerInterface $handler */
        foreach ($this->_handlers as $handler) {
            if ($this->getValidEntityType($rowData, $handler)) {
                $rowData = $handler->prepareRowForUpdate($rowData);
            }
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
        /** @var EntityHandlerInterface $handler */
        foreach ($this->_handlers as $handler) {
            if ($this->getValidEntityType($rowData, $handler)) {
                $rowData = $handler->prepareRowForReplace($rowData);
            }
        }
        return $rowData;
    }

    /**
     * Prepare row data for delete behaviour
     *
     * @param array $rowData
     * @return array
     */
    public function prepareRowForDelete(array $rowData)
    {
        /** @var EntityHandlerInterface $handler */
        foreach ($this->_handlers as $handler) {
            if ($this->getValidEntityType($rowData, $handler)) {
                $rowData = $handler->prepareRowForDelete($rowData);
            }
        }
        return $rowData;
    }
}
