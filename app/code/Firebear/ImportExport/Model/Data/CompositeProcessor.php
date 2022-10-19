<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Data;

use Magento\Framework\Exception\ConfigurationMismatchException;

/**
 * Composite processor
 */
class CompositeProcessor implements ProcessorInterface
{
    /**
     * @var ProcessorInterface[]
     */
    private $processorList;

    /**
     * @param ProcessorInterface[] $processorList
     */
    public function __construct(
        array $processorList = []
    ) {
        $this->processorList = $processorList;
    }

    /**
     * Process entity data
     *
     * @param array $data
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function process($data)
    {
        foreach ($this->processorList as $processor) {
            if (!$processor instanceof ProcessorInterface) {
                throw new ConfigurationMismatchException(
                    __("Data processor must implement %1", ProcessorInterface::class)
                );
            }
            $data = $processor->process($data);
        }
        return $data;
    }
}
