<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Api\Data;

/**
 * Interface ExportHistoryInterface
 * @package Firebear\ExportExport\Api\Data
 */
interface ExportHistoryInterface
{
    const HISTORY_ID = 'history_id';

    const JOB_ID = 'job_id';

    const STARTED_AT = 'started_at';

    const FINISHED_AT = 'finished_at';

    const TYPE = 'type';

    const FILE = 'file';

    const TEMP_FILE = 'temp_file';

    const MOVED = 'is_moved';

    const DB_LOG_STORAGE = 'db_log_storage';

    const LOG_CONTENT = 'log_content';

    /**
     * @return int
     */
    public function getId();

    /**
     * @return int
     */
    public function getJobId();

    /**
     * @return string
     */
    public function getStartedAt();

    /**
     * @return string
     */
    public function getFinishedAt();

    /**
     * @return string
     */
    public function getType();

    /**
     * @return string
     */
    public function getFile();

    /**
     * @return string
     */
    public function getTempFile();

    /**
     * @param int $id
     *
     * @return ExportHistoryInterface
     */
    public function setId($id);

    /**
     * @param int $jobId
     *
     * @return ExportHistoryInterface
     */
    public function setJobId($jobId);

    /**
     * @param string $start
     *
     * @return ExportHistoryInterface
     */
    public function setStartedAt($start);

    /**
     * @param string $finish
     *
     * @return ExportHistoryInterface
     */
    public function setFinishedAt($finish);

    /**
     * @param string $type
     *
     * @return ExportHistoryInterface
     */
    public function setType($type);

    /**
     * @param string $file
     *
     * @return ExportHistoryInterface
     */
    public function setFile($file);

    /**
     * @param string $file
     *
     * @return ExportHistoryInterface
     */
    public function setTempFile($file);

    /**
     * @param bool $moved
     *
     * @return bool
     */
    public function isMoved($moved = null);

    /**
     * @param bool $enable
     * @return ExportHistoryInterface
     */
    public function setDbLogStorage(bool $enable): ExportHistoryInterface;

    /**
     * @return bool
     */
    public function getDbLogStorage(): bool;

    /**
     * @param string $logContent
     * @return ExportHistoryInterface
     */
    public function setLogContent(string $logContent): ExportHistoryInterface;

    /**
     * @return string
     */
    public function getLogContent(): string;
}
