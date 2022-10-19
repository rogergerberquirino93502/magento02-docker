<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Api\Export;

use Firebear\ImportExport\Api\Export\SearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * Find export jobs by SearchCriteria command (Service Provider Interface - SPI)
 *
 * @api
 */
interface GetListInterface
{
    /**
     * Get job list
     *
     * @param SearchCriteriaInterface|null $searchCriteria
     * @return SearchResultsInterface
     */
    public function execute(SearchCriteriaInterface $searchCriteria = null);
}
