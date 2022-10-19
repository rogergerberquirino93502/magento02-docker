<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Api\Data;

/**
 * Interface ImportHistoryInterface
 * @package Firebear\ImportExport\Api\Data
 */
interface ImportHistoryInterface
{
    const HISTORY_ID = 'history_id';

    const JOB_ID = 'job_id';

    const STARTED_AT = 'started_at';

    const FINISHED_AT = 'finished_at';

    const TYPE = 'type';

    const FILE = 'file';

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
     * @param int $id
     *
     * @return ImportHistoryInterface
     */
    public function setId($id);

    /**
     * @param int $jobId
     *
     * @return ImportHistoryInterface
     */
    public function setJobId($jobId);

    /**
     * @param bool $enable
     * @return ImportHistoryInterface
     */
    public function setDbLogStorage(bool $enable): ImportHistoryInterface;

    /**
     * @return bool
     */
    public function getDbLogStorage(): bool;

    /**
     * @param string $logContent
     * @return ImportHistoryInterface
     */
    public function setLogContent(string $logContent): ImportHistoryInterface;

    /**
     * @return string
     */
    public function getLogContent(): string;

    /**
     * @param string $start
     *
     * @return ImportHistoryInterface
     */
    public function setStartedAt($start);

    /**
     * @param string $finish
     *
     * @return ImportHistoryInterface
     */
    public function setFinishedAt($finish);

    /**
     * @param string $type
     *
     * @return ImportHistoryInterface
     */
    public function setType($type);

    /**
     * @param string $file
     *
     * @return ImportHistoryInterface
     */
    public function setFile($file);
}
