<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Data;

use Magento\Framework\Exception\ConfigurationMismatchException;

/**
 * Processor interface
 */
interface ProcessorInterface
{
    /**
     * Process entity data
     *
     * @param array $data
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function process($data);
}
