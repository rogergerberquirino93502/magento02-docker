<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export;

use Magento\Framework\Model\AbstractModel;
use Firebear\ImportExport\Api\Data\ExportHistoryInterface;
use Firebear\ImportExport\Model\ResourceModel\Export\History as ResourceModelHistory;

/**
 * Class History
 *
 * @package Firebear\ImportExport\Model\Export
 */
class History extends AbstractModel implements ExportHistoryInterface
{
    protected function _construct()
    {
        $this->_init(ResourceModelHistory::class);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->getData(self::HISTORY_ID);
    }

    /**
     * @return int
     */
    public function getJobId()
    {
        return $this->getData(self::JOB_ID);
    }

    /**
     * @return string
     */
    public function getStartedAt()
    {
        return $this->getData(self::STARTED_AT);
    }

    /**
     * @return string
     */
    public function getFinishedAt()
    {
        return $this->getData(self::FINISHED_AT);
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->getData(self::TYPE);
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->getData(self::FILE);
    }

    /**
     * @return string
     */
    public function getTempFile()
    {
        return $this->getData(self::TEMP_FILE);
    }

    /**
     * @param int $id
     *
     * @return ExportHistoryInterface
     */
    public function setId($id)
    {
        $this->setData(self::HISTORY_ID, $id);

        return $this;
    }

    /**
     * @param int $jobId
     *
     * @return ExportHistoryInterface
     */
    public function setJobId($jobId)
    {
        $this->setData(self::JOB_ID, $jobId);

        return $this;
    }

    /**
     * @param string $start
     *
     * @return ExportHistoryInterface
     */
    public function setStartedAt($start)
    {
        $this->setData(self::STARTED_AT, $start);

        return $this;
    }

    /**
     * @param string $finish
     *
     * @return ExportHistoryInterface
     */
    public function setFinishedAt($finish)
    {
        $this->setData(self::FINISHED_AT, $finish);

        return $this;
    }

    /**
     * @param string $type
     *
     * @return ExportHistoryInterface
     */
    public function setType($type)
    {
        $this->setData(self::TYPE, $type);

        return $this;
    }

    /**
     * @param string $file
     *
     * @return ExportHistoryInterface
     */
    public function setFile($file)
    {
        $this->setData(self::FILE, $file);

        return $this;
    }

    /**
     * @param string $file
     *
     * @return ExportHistoryInterface
     */
    public function setTempFile($file)
    {
        $this->setData(self::TEMP_FILE, $file);

        return $this;
    }

    /**
     * @param bool $moved
     *
     * @return bool
     */
    public function isMoved($moved = null)
    {
        if (null !== $moved) {
            $this->setData(self::MOVED, $moved);
        }
        return (bool)$this->getData(self::MOVED);
    }

    /**
     * @inheritDoc
     */
    public function setDbLogStorage(bool $enable): ExportHistoryInterface
    {
        $this->setData(self::DB_LOG_STORAGE, $enable);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setLogContent(string $logContent): ExportHistoryInterface
    {
        $this->setData(self::LOG_CONTENT, $logContent);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getDbLogStorage(): bool
    {
        return $this->getData(self::DB_LOG_STORAGE) ?? false;
    }

    /**
     * @inheritDoc
     */
    public function getLogContent(): string
    {
        return $this->getData(self::LOG_CONTENT) ?? '';
    }
}
