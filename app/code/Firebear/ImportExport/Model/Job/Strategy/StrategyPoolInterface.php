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
interface StrategyPoolInterface
{
    /**
     * Retrieve strategies
     *
     * @return mixed[]
     */
    public function getStrategies();

    /**
     * Retrieve strategies instantiated
     *
     * @return StrategyInterface[]
     * @throws LocalizedException
     */
    public function getStrategiesInstances();
}
