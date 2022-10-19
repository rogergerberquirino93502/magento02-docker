<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Import\ErrorProcessing;

use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregator;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;

/**
 * Class ProcessingErrorAggregatorExtended
 * @package Magento\ImportExport\Model\Import\ErrorProcessing
 */
class ProcessingErrorAggregatorExtended extends ProcessingErrorAggregator
{
    /**
     * Check if import has to be terminated
     *
     * @return bool
     */
    public function hasToBeTerminated()
    {
        return $this->isErrorLimitExceeded();
    }
}
