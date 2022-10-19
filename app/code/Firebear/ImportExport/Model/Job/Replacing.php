<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Job;

use Firebear\ImportExport\Api\Data\JobReplacingInterface;
use Magento\Framework\Model\AbstractModel;
use Firebear\ImportExport\Model\ResourceModel\Job\Replacing as ResourceModelReplacing;

/**
 * Class Replacing
 * @package Firebear\ImportExport\Model\Job
 */
class Replacing extends AbstractModel implements JobReplacingInterface
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(ResourceModelReplacing::class);
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
     * @return string
     */
    public function getAttributeCode()
    {
        return $this->getData(self::ATTRIBUTE_CODE);
    }

    /**
     * @return int
     */
    public function getTarget()
    {
        return $this->getData(self::TARGET);
    }

    /**
     * @return bool
     */
    public function getIsCaseSensitive()
    {
        return (bool) $this->getData(self::IS_CASE_SENSITIVE);
    }

    /**
     * @return string
     */
    public function getFind()
    {
        return $this->getData(self::FIND);
    }

    /**
     * @return string|null
     */
    public function getReplace()
    {
        return $this->getData(self::REPLACE);
    }

    /**
     * @param int $value
     * @return $this
     */
    public function setId($value)
    {
        return $this->setData(self::ENTITY_ID, $value);
    }

    /**
     * @param int $value
     * @return $this
     */
    public function setJobId($value)
    {
        return $this->setData(self::JOB_ID, $value);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setAttributeCode($value)
    {
        return $this->setData(self::ATTRIBUTE_CODE, $value);
    }

    /**
     * @param int $value
     * @return $this
     */
    public function setTarget($value)
    {
        return $this->setData(self::TARGET, $value);
    }

    /**
     * @param bool|int $value
     * @return $this
     */
    public function setIsCaseSensitive($value)
    {
        return $this->setData(self::IS_CASE_SENSITIVE, (bool) $value);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setFind($value)
    {
        return $this->setData(self::FIND, $value);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setReplace($value)
    {
        return $this->setData(self::REPLACE, $value);
    }

    /**
     * @return string|null
     */
    public function getEntityType()
    {
        return $this->getData(self::ENTITY_TYPE);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setEntityType($value)
    {
        return $this->setData(self::ENTITY_TYPE, $value);
    }
}
