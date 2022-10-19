<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Job\Handler;

use Firebear\ImportExport\Api\Data\ImportInterface;
use Firebear\ImportExport\Model\Email\Sender;

/**
 * @api
 */
class EmailHandler implements HandlerInterface
{
    /**
     * Email sender
     *
     * @var Sender
     */
    private $sender;

    /**
     * Constructor
     *
     * @param Sender $sender
     */
    public function __construct(
        Sender $sender
    ) {
        $this->sender = $sender;
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
        $this->sender->sendEmail($job, $file, $status);
    }
}
