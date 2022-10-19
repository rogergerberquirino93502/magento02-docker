<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

declare(strict_types=1);

namespace Firebear\ImportExport\Model\Export\FilterProcessor;

use Magento\Framework\Data\Collection;
use Magento\Framework\Exception\LocalizedException;

/**
 * @api
 */
class FilterProcessorAggregator
{
    /**
     * @var FilterProcessorInterface[]
     */
    private $handler;

    /**
     * @param FilterProcessorInterface[] $handler
     * @throws LocalizedException
     */
    public function __construct(array $handler = [])
    {
        foreach ($handler as $filterProcessor) {
            if (!($filterProcessor instanceof FilterProcessorInterface)) {
                throw new LocalizedException(__(
                    'Filter handler must be instance of "%interface"',
                    ['interface' => FilterProcessorInterface::class]
                ));
            }
        }

        $this->handler = $handler;
    }

    /**
     * @param string $type
     * @param Collection $collection
     * @param string $columnName
     * @param string|array $value
     * @throws LocalizedException
     */
    public function process($type, Collection $collection, $columnName, $value)
    {
        if (!isset($this->handler[$type])) {
            throw new LocalizedException(__(
                'No filter processor for "%type" given.',
                ['type' => $type]
            ));
        }
        $this->handler[$type]->process($collection, $columnName, $value);
    }
}
