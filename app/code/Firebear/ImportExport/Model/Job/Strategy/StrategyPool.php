<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Job\Strategy;

use Magento\Framework\Exception\LocalizedException;

/**
 * @api
 */
class StrategyPool implements StrategyPoolInterface
{
    /**
     * @var mixed[]
     */
    protected $strategies = [];

    /**
     * @var StrategyInterface[]
     */
    protected $strategiesInstances = [];

    /**
     * @var StrategyFactory
     */
    protected $factory;

    /**
     * @param StrategyFactory $factory
     * @param mixed[] $strategies
     */
    public function __construct(
        StrategyFactory $factory,
        array $strategies = []
    ) {
        $this->factory = $factory;
        $this->strategies = $this->sort($strategies);
    }

    /**
     * Retrieve strategies
     *
     * @return mixed[]
     */
    public function getStrategies()
    {
        return $this->strategies;
    }

    /**
     * Retrieve strategies instantiated
     *
     * @return StrategyInterface[]
     * @throws LocalizedException
     */
    public function getStrategiesInstances()
    {
        if ($this->strategiesInstances) {
            return $this->strategiesInstances;
        }

        foreach ($this->strategies as $strategy) {
            if (empty($strategy['class'])) {
                throw new LocalizedException(__('The parameter "class" is missing. Set the "class" and try again.'));
            }

            if (empty($strategy['sortOrder'])) {
                throw new LocalizedException(
                    __('The parameter "sortOrder" is missing. Set the "sortOrder" and try again.')
                );
            }

            $this->strategiesInstances[] = $this->factory->create($strategy['class']);
        }

        return $this->strategiesInstances;
    }

    /**
     * Sorting strategies according to sort order
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
