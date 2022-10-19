<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Api\Data;

interface AbstractInterface
{
    /**
     * Job's statuses
     */
    const STATUS_ENABLED    = 1;
    const STATUS_DISABLED   = 0;

    /**
     * Frequency Modes
     */
    const FREQUENCY_NONE    = 'NONE';
    const FREQUENCY_MINUTE  = 'MIN';
    const FREQUENCY_HOUR    = 'H';
    const FREQUENCY_DAY     = 'D';
    const FREQUENCY_WEEK    = 'W';
    const FREQUENCY_MONTH   = 'M';
    const FREQUENCY_CUSTOM  = 'C';

    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const ENTITY_ID         = 'entity_id';
    const TITLE             = 'title';
    const IS_ACTIVE         = 'is_active';
    const CRON              = 'cron';
    const FREQUENCY         = 'frequency';
    const ENTITY            = 'entity';
    const BEHAVIOR_DATA     = 'behavior_data';
    const SOURCE_DATA       = 'source_data';
    const FILE_UPDATED_AT   = 'file_updated_at';
    const MAPPING           = 'mapping';
    const PRICE_RULES       = 'price_rules';

    /**
     * @return int|null
     */
    public function getId();

    /**
     * @return string
     */
    public function getTitle();

    /**
     * @return int
     */
    public function getIsActive();

    /**
     * @return string|null
     */
    public function getCron();

    /**
     * @return string
     */
    public function getFrequency();

    /**
     * @return string
     */
    public function getEntity();

    /**
     * @return mixed
     */
    public function getBehaviorData();

    /**
     * @return mixed
     */
    public function getSourceData();

    /**
     * @return string|null
     */
    public function getFileUpdatedAt();

    /**
     * @param $jobId
     *
     * @return AbstractInterface
     */
    public function setId($jobId);

    /**
     * @param $title
     *
     * @return AbstractInterface
     */
    public function setTitle($title);

    /**
     * @param $isActive
     *
     * @return AbstractInterface
     */
    public function setIsActive($isActive);

    /**
     * @param $cron
     *
     * @return AbstractInterface
     */
    public function setCron($cron);

    /**
     * @param $frequency
     *
     * @return AbstractInterface
     */
    public function setFrequency($frequency);

    /**
     * @param $entity
     *
     * @return AbstractInterface
     */
    public function setEntity($entity);

    /**
     * @param mixed[] $behavior
     *
     * @return AbstractInterface
     */
    public function setBehaviorData($behavior);

    /**
     * @param mixed[] $source
     *
     * @return AbstractInterface
     */
    public function setSourceData($source);

    /**
     * @param $date
     *
     * @return AbstractInterface
     */
    public function setFileUpdatedAt($date);
}
