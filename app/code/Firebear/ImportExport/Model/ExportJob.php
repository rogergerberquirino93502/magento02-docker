<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

declare(strict_types=1);

namespace Firebear\ImportExport\Model;

use Firebear\ImportExport\Model\ExportJob\Event;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Firebear\ImportExport\Api\Data\ExportInterface;
use Firebear\ImportExport\Model\ResourceModel\ExportJob as ResourceModelExportJob;
use Firebear\ImportExport\Model\ResourceModel\ExportJob\Event\CollectionFactory as CollectionFactory;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

/**
 * Class ExportJob
 *
 * @package Firebear\ImportExport\Model
 */
class ExportJob extends AbstractModel implements ExportInterface
{
    /**
     * @param Context $context
     * @param Registry $registry
     * @param CollectionFactory $collectionEventFactory
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        CollectionFactory $collectionEventFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->collectionEventFactory = $collectionEventFactory;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModelExportJob::class);
    }

    /**
     * @var ResourceModel\ExportJob\Event\CollectionFactory
     */
    protected $collectionEventFactory;
    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->getData(self::TITLE);
    }

    /**
     * @return int
     */
    public function getIsActive()
    {
        return $this->getData(self::IS_ACTIVE);
    }

    /**
     * @return string|null
     */
    public function getCron()
    {
        return $this->getData(self::CRON);
    }

    /**
     * @return string
     */
    public function getFrequency()
    {
        return $this->getData(self::FREQUENCY);
    }

    /**
     * @return string
     */
    public function getEntity()
    {
        return $this->getData(self::ENTITY);
    }

    /**
     * @return mixed[]
     */
    public function getBehaviorData()
    {
        return  $this->getData(self::BEHAVIOR_DATA);
    }

    /**
     * @return mixed[]
     */
    public function getSourceData()
    {
        return $this->getData(self::SOURCE_DATA);
    }

    /**
     * @return string|null
     */
    public function getFileUpdatedAt()
    {
        return $this->getData(self::FILE_UPDATED_AT);
    }

    /**
     * @return mixed[]
     */
    public function getExportSource()
    {
        return $this->getData(self::EXPORT_SOURCE);
    }

    /**
     * @return string
     */
    public function getXslt()
    {
        return $this->getData(self::XSLT);
    }

    /**
     * @param $jobId
     *
     * @return void
     */
    public function setId($jobId)
    {
        $this->setData(self::ENTITY_ID, $jobId);
    }

    /**
     * @param $title
     *
     * @return void
     */
    public function setTitle($title)
    {
        $this->setData(self::TITLE, $title);
    }

    /**
     * @param $isActive
     *
     * @return void
     */
    public function setIsActive($isActive)
    {
        $this->setData(self::IS_ACTIVE, $isActive);
    }

    /**
     * @param $cron
     *
     * @return void
     */
    public function setCron($cron)
    {
        $this->setData(self::CRON, $cron);
    }

    /**
     * @param $frequency
     *
     * @return void
     */
    public function setFrequency($frequency)
    {
        $this->setData(self::FREQUENCY, $frequency);
    }

    /**
     * @param $entity
     *
     * @return void
     */
    public function setEntity($entity)
    {
        $this->setData(self::ENTITY, $entity);
    }

    /**
     * @param mixed[] $behavior
     *
     * @return void
     */
    public function setBehaviorData($behavior)
    {
        $this->setData(self::BEHAVIOR_DATA, $behavior);
    }

    /**
     * @param mixed[] $source
     *
     * @return void
     */
    public function setSourceData($source)
    {
        $this->setData(self::SOURCE_DATA, $source);
    }

    /**
     * @param $date
     *
     * @return void
     */
    public function setFileUpdatedAt($date)
    {
        $this->setData(self::FILE_UPDATED_AT, $date);
    }

    /**
     * @param mixed[] $source
     *
     * @return void
     */
    public function setExportSource($source)
    {
        $this->setData(self::EXPORT_SOURCE, $source);
    }

    /**
     * @param $xslt
     *
     * @return ExportJob
     */
    public function setXslt($xslt)
    {
        return $this->setData(self::XSLT, $xslt);
    }

    /**
     * @return mixed
     */
    public function getEvent()
    {
        if ($this->getData(self::EVENT) == null) {
            $this->setData(
                self::EVENT,
                $this->getEventCollection()->getItems()
            );
        }
        return $this->getData(self::EVENT);
    }

    /**
     * @return mixed
     */
    private function getEventCollection()
    {
        $collection = $this->collectionEventFactory->create()->addFieldToFilter('job_id', $this->getId());

        return $collection;
    }

    /**
     * @param Event $event
     *
     * @return $this
     */
    public function addEvent(Event $event)
    {
        if (!$event->getJobId()) {
            $this->setEvents(array_merge($this->getEvent(), [$event->getEvent()]));
        }

        return $this;
    }

    /**
     * @param $events
     *
     * @return mixed
     */
    public function setEvents($events)
    {
        return $this->setData(self::EVENT, $events);
    }
}
