<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Api\Data;

/**
 * Interface JobReplacingInterface
 * @package Firebear\ImportExport\Api\Data
 */
interface JobReplacingInterface
{
    const ENTITY_ID         = 'entity_id';
    const JOB_ID            = 'job_id';
    const ATTRIBUTE_CODE    = 'attribute_code';
    const ENTITY_TYPE       = 'entity_type';
    const TARGET            = 'target';
    const IS_CASE_SENSITIVE = 'is_case_sensitive';
    const FIND              = 'find';
    const REPLACE           = 'replace';

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
    public function getAttributeCode();

    /**
     * @return int
     */
    public function getTarget();

    /**
     * @return bool
     */
    public function getIsCaseSensitive();

    /**
     * @return string
     */
    public function getFind();

    /**
     * @return string|null
     */
    public function getReplace();

    /**
     * @return string|null
     */
    public function getEntityType();

    /**
     * @param int $value
     * @return $this
     */
    public function setId($value);

    /**
     * @param int $value
     * @return $this
     */
    public function setJobId($value);

    /**
     * @param string $value
     * @return $this
     */
    public function setAttributeCode($value);

    /**
     * @param int $value
     * @return $this
     */
    public function setTarget($value);

    /**
     * @param int|bool $value
     * @return $this
     */
    public function setIsCaseSensitive($value);

    /**
     * @param string $value
     * @return $this
     */
    public function setFind($value);

    /**
     * @param string $value
     * @return $this
     */
    public function setEntityType($value);

    /**
     * @param string $value
     * @return $this
     */
    public function setReplace($value);
}
