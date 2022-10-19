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
interface HandlerPoolInterface
{
    /**
     * Retrieve handlers
     *
     * @return mixed[]
     */
    public function getHandlers();

    /**
     * Retrieve handlers instantiated
     *
     * @return HandlerInterface[]
     * @throws LocalizedException
     */
    public function getHandlersInstances();
}
