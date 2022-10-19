<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Migration;

use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @api
 */
interface JobInterface
{
    /**
     * Process table
     *
     * @param OutputInterface $output
     * @param AdditionalOptions|null $additionalOptions
     *
     * @throws LocalizedException
     */
    public function job($output, $additionalOptions = null);
}
