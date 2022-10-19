<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Api\Import;

use Firebear\ImportExport\Api\Import\ConsoleInterface;
use Firebear\ImportExport\Helper\Data as Helper;

/**
 * Console command (Service Provider Interface - SPI)
 *
 * @api
 */
class Console implements ConsoleInterface
{
    /**
     * @var Helper
     */
    private $helper;

    /**
     * Initialize command
     *
     * @param Helper $helper
     */
    public function __construct(
        Helper $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * Get console info
     *
     * @param string $file
     * @param int $counter
     * @return string
     */
    public function execute($file, $counter)
    {
        return $this->helper->scopeRun($file, $counter);
    }
}
