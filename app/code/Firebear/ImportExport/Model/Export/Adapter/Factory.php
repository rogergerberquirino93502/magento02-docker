<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export\Adapter;

use InvalidArgumentException;
use Magento\ImportExport\Model\Export\Adapter\AbstractAdapter;
use Magento\ImportExport\Model\Export\Adapter\Factory as MagentoFactory;

/**
 * Class Export Adapter Factory
 *
 * @package Firebear\ImportExport\Model\Export\Adapter
 */
class Factory extends MagentoFactory
{
    /**
     * Create New Export Adapter Instance
     *
     * @param string $className
     * @param array $data
     * @return AbstractAdapter
     * @throws InvalidArgumentException
     */
    public function create($className, array $data = [])
    {
        if (!$className) {
            throw new InvalidArgumentException('Incorrect class name');
        }
        return $this->_objectManager->create($className, $data);
    }
}
