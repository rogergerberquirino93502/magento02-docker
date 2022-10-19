<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Job\Handler;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Handler factory
 */
class HandlerFactory
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
     * Create handler
     *
     * @param string $className
     * @param mixed[] $data
     * @return HandlerInterface
     * @throws LocalizedException
     */
    public function create($className, array $data = [])
    {
        $handler = $this->objectManager->create($className, $data);
        if (!$handler instanceof HandlerInterface) {
            throw new LocalizedException(
                __('Type "' . $className . '" is not instance on ' . HandlerInterface::class)
            );
        }
        return $handler;
    }
}
