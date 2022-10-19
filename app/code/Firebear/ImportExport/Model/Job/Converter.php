<?php
namespace Firebear\ImportExport\Model\Job;

use Firebear\ImportExport\Api\Data\ImportInterface;
use Firebear\ImportExport\Api\Data\ImportInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Reflection\DataObjectProcessor;
use Firebear\ImportExport\Model\AbstractJobConverter;

/**
 * Class Converter
 *
 * @package Firebear\ImportExport\Model\ExportJob
 */
class Converter extends AbstractJobConverter
{
    /**
     * Converter constructor.
     *
     * @param ImportInterfaceFactory $importFactory
     * @inheritdoc
     */
    public function __construct(
        ImportInterfaceFactory $importFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor
    ) {
        $this->setFactory($importFactory);

        parent::__construct($dataObjectHelper, $dataObjectProcessor);
    }

    /**
     * @inheritdoc
     */
    protected function getInterfaceName()
    {
        return ImportInterface::class;
    }
}
