<?php
namespace Firebear\ImportExport\Model\ExportJob;

use Firebear\ImportExport\Api\Data\ExportInterface;
use Firebear\ImportExport\Api\Data\ExportInterfaceFactory;
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
     * @param ExportInterfaceFactory $exportFactory
     * @inheritdoc
     */
    public function __construct(
        ExportInterfaceFactory $exportFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor
    ) {
        $this->setFactory($exportFactory);

        parent::__construct($dataObjectHelper, $dataObjectProcessor);
    }

    /**
     * @inheritdoc
     */
    protected function getInterfaceName()
    {
        return ExportInterface::class;
    }
}
