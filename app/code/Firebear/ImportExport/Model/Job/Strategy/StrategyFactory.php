<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Job\Strategy;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Strategy factory
 */
class StrategyFactory
{
    /**
     * Object Manager
     *
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Construct
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * Create strategy
     *
     * @param string $className
     * @param mixed[] $data
     * @return StrategyInterface
     * @throws LocalizedException
     */
    public function create($className, array $data = [])
    {
        $strategy = $this->objectManager->create($className, $data);
        if (!$strategy instanceof StrategyInterface) {
            throw new LocalizedException(
                __('Type "' . $className . '" is not instance on ' . StrategyInterface::class)
            );
        }
        return $strategy;
    }
}
