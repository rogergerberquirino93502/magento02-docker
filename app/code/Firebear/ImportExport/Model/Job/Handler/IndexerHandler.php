<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Job\Handler;

use Firebear\ImportExport\Api\Data\ImportInterface;
use Firebear\ImportExport\Helper\Data as Helper;

/**
 * @api
 */
class IndexerHandler implements HandlerInterface
{
    /**
     * @var Helper
     */
    private $helper;

    /**
     * Constructor
     *
     * @param Helper $helper
     */
    public function __construct(
        Helper $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * Execute the handler
     *
     * @param ImportInterface $job
     * @param string $file
     * @param int $status
     * @return void
     */
    public function execute(ImportInterface $job, $file, $status)
    {
        if ($status) {
            $this->helper->processReindex($file, $job->getEntityId());
        }
    }
}
