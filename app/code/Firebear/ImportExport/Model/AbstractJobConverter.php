<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model;

use Firebear\ImportExport\Api\Data\ExportInterface;
use Firebear\ImportExport\Api\Data\ImportInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Model\AbstractModel;
use Firebear\ImportExport\Api\Data\ImportInterfaceFactory;
use Firebear\ImportExport\Api\Data\ExportInterfaceFactory;

/**
 * Class AbstractJobConverter
 *
 * @package Firebear\ImportExport\Model
 */
abstract class AbstractJobConverter
{
    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var DataObjectProcessor
     */
    private $dataObjectProcessor;

    /**
     * @var ExportInterfaceFactory|ImportInterfaceFactory
     */
    private $factory;

    /**
     * AbstractJobConverter constructor.
     *
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     */
    public function __construct(
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor
    ) {
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataObjectProcessor = $dataObjectProcessor;
    }

    /**
     * Retrieves data object using model
     *
     * @param AbstractModel $model
     * @return ImportInterface
     */
    public function getDataObject($model)
    {
        /** @var ImportInterface $object */
        $object = $this->getFactory()->create();
        $this->dataObjectHelper->populateWithArray(
            $object,
            $this->dataObjectProcessor->buildOutputDataArray($model, $this->getInterfaceName()),
            $this->getInterfaceName()
        );

        return $object;
    }

    /**
     * Retrieves data object by form data
     *
     * @param array $data
     * @return ImportInterface|ExportInterface
     */
    public function getDataObjectByData($data)
    {
        /** @var ImportInterface $object */
        $object = $this->getFactory()->create();
        $this->dataObjectHelper->populateWithArray(
            $object,
            $data,
            $this->getInterfaceName()
        );

        return $object;
    }

    /**
     * Set factory
     *
     * @param ExportInterfaceFactory|ImportInterfaceFactory $factory
     * @return $this
     */
    protected function setFactory($factory)
    {
        $this->factory = $factory;
        return $this;
    }

    /**
     * Get factory
     *
     * @return ExportInterfaceFactory|ImportInterfaceFactory
     */
    protected function getFactory()
    {
        return $this->factory;
    }

    /**
     * Get interface name
     *
     * @return string
     */
    abstract protected function getInterfaceName();
}
