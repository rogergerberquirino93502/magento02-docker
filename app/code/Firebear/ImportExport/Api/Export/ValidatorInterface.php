<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Api\Export;

use Firebear\ImportExport\Api\Data\ExportInterface;

/**
 * Responsible for Job validation
 *
 * @api
 */
interface ValidatorInterface
{
    /**
     * @inheritdoc
     */
    public function validate(ExportInterface $job);
}
