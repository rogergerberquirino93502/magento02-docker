<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Job;

use Magento\Framework\Model\AbstractModel;
use Firebear\ImportExport\Api\Data\ImportMappingInterface;
use Firebear\ImportExport\Model\ResourceModel\Job\Mapping as ResourceModelMapping;

/**
 * Class Mapping
 *
 * @package Firebear\ImportExport\Model\Job
 */
class Mapping extends AbstractModel implements ImportMappingInterface
{

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModelMapping::class);
    }
    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * @return int
     */
    public function getJobId()
    {
        return $this->getData(self::JOB_ID);
    }

    /**
     * @return int
     */
    public function getAttributeId()
    {
        return $this->getData(self::ATTRIBUTE_ID);
    }

    /**
     * @return string
     */
    public function getSpecialAttributeId()
    {
        return $this->getData(self::SPECIAL_ATTRIBUTE);
    }

    /**
     * @return string
     */
    public function getImportCode()
    {
        return $this->getData(self::IMPORT_CODE);
    }

    /**
     * @return string
     */
    public function getDefaultValue()
    {
        return $this->getData(self::DEFAULT_VALUE);
    }

    /**
     * @return int
     */
    public function getCustom()
    {
        return $this->getData(self::CUSTOM);
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->getData(self::POSITION);
    }

    /**
     * @param $id
     *
     * @return ImportMappingInterface
     */
    public function setId($id)
    {
        $this->setData(self::ENTITY_ID, $id);
    }

    /**
     * @param $jobId
     *
     * @return ImportMappingInterface
     */
    public function setJobId($jobId)
    {
        $this->setData(self::JOB_ID, $jobId);
    }

    /**
     * @param $attrId
     *
     * @return ImportMappingInterface
     */
    public function setAttributeId($attrId)
    {
        $this->setData(self::ATTRIBUTE_ID, $attrId);
    }

    /**
     * @param $specAttr
     *
     * @return ImportMappingInterface
     */
    public function setSpecialAttributeId($specAttr)
    {
        $this->setData(self::SPECIAL_ATTRIBUTE, $specAttr);
    }

    /**
     * @param $code
     *
     * @return ImportMappingInterface
     */
    public function setImportCode($code)
    {
        $this->setData(self::IMPORT_CODE, $code);
    }

    /**
     * @param $value
     *
     * @return ImportMappingInterface
     */
    public function setDefaultValue($value)
    {
        $this->setData(self::DEFAULT_VALUE, $value);
    }

    /**
     * @param $value
     *
     * @return ImportMappingInterface
     */
    public function setCustom($value)
    {
        $this->setData(self::CUSTOM, $value);
    }

    /**
     * @param $value
     *
     * @return ImportMappingInterface
     */
    public function setPosition($value)
    {
        $this->setData(self::POSITION, $value);
    }
}
