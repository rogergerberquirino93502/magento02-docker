<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Api\Data;

/**
 * Interface ImportMappingInterface
 *
 * @package Firebear\ImportExport\Api\Data
 */
interface ImportMappingInterface
{
    const ENTITY_ID = 'entity_id';

    const JOB_ID = 'job_id';

    const ATTRIBUTE_ID = 'attribute_id';

    const SPECIAL_ATTRIBUTE = 'special_attribute';

    const IMPORT_CODE = 'import_code';

    const DEFAULT_VALUE = 'default_value';

    const CUSTOM = 'custom';

    const POSITION = 'position';

    /**
     * @return int|null
     */
    public function getId();

    /**
     * @return int
     */
    public function getJobId();

    /**
     * @return int
     */
    public function getAttributeId();

    /**
     * @return string
     */
    public function getSpecialAttributeId();

    /**
     * @return string
     */
    public function getImportCode();

    /**
     * @return string
     */
    public function getDefaultValue();

    /**
     * @return int
     */
    public function getCustom();

    /**
     * @return int
     */
    public function getPosition();

    /**
     * @param $id
     *
     * @return ImportMappingInterface
     */
    public function setId($id);

    /**
     * @param $jobId
     *
     * @return ImportMappingInterface
     */
    public function setJobId($jobId);

    /**
     * @param $attrId
     *
     * @return ImportMappingInterface
     */
    public function setAttributeId($attrId);

    /**
     * @param $specAttr
     *
     * @return ImportMappingInterface
     */
    public function setSpecialAttributeId($specAttr);

    /**
     * @param $code
     *
     * @return ImportMappingInterface
     */
    public function setImportCode($code);

    /**
     * @param $value
     * @return ImportMappingInterface
     */
    public function setCustom($value);

    /**
     * @param $value
     * @return ImportMappingInterface
     */
    public function setPosition($value);
}
