<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
declare(strict_types=1);

namespace Firebear\ImportExport\Api\Data;

/**
 * @api
 */
interface ExportEventInterface
{
    const EVENT = 'event';

    const JOB_ID = 'job_id';

    /**
     * @return string
     */
    public function getEvent(): string;

    /**
     * @param string $name
     * @return void
     */
    public function setEvent(string $name);

    /**
     * @return int
     */
    public function getJobId(): int;

    /**
     * @param int $id
     * @return void
     */
    public function setJobId(int $id);
}
