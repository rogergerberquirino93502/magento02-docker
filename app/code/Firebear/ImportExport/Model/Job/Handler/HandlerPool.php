<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Job\Handler;

use Magento\Framework\Exception\LocalizedException;

/**
 * @api
 */
class HandlerPool implements HandlerPoolInterface
{
    /**
     * @var mixed[]
     */
    protected $handlers = [];

    /**
     * @var HandlerInterface[]
     */
    protected $handlersInstances = [];

    /**
     * @var HandlerFactory
     */
    protected $factory;

    /**
     * @param HandlerFactory $factory
     * @param mixed[] $handlers
     */
    public function __construct(
        HandlerFactory $factory,
        array $handlers = []
    ) {
        $this->factory = $factory;
        $this->handlers = $this->sort($handlers);
    }

    /**
     * Retrieve handlers
     *
     * @return mixed[]
     */
    public function getHandlers()
    {
        return $this->handlers;
    }

    /**
     * Retrieve handlers instantiated
     *
     * @return HandlerInterface[]
     * @throws LocalizedException
     */
    public function getHandlersInstances()
    {
        if ($this->handlersInstances) {
            return $this->handlersInstances;
        }

        foreach ($this->handlers as $handler) {
            if (empty($handler['class'])) {
                throw new LocalizedException(__('The parameter "class" is missing. Set the "class" and try again.'));
            }

            if (empty($handler['sortOrder'])) {
                throw new LocalizedException(
                    __('The parameter "sortOrder" is missing. Set the "sortOrder" and try again.')
                );
            }

            $this->handlersInstances[] = $this->factory->create($handler['class']);
        }

        return $this->handlersInstances;
    }

    /**
     * Sorting handlers according to sort order
     *
     * @param mixed[] $data
     * @return mixed[]
     */
    protected function sort(array $data)
    {
        usort($data, function (array $a, array $b) {
            return $this->getSortOrder($a) <=> $this->getSortOrder($b);
        });

        return $data;
    }

    /**
     * Retrieve sort order from array
     *
     * @param mixed[] $variable
     * @return int
     */
    protected function getSortOrder(array $variable)
    {
        return !empty($variable['sortOrder']) ? $variable['sortOrder'] : 0;
    }
}
