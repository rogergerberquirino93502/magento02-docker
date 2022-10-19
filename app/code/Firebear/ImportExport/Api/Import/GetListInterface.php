<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Api\Import;

use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * Find jobs by SearchCriteria command (Service Provider Interface - SPI)
 *
 * @api
 */
interface GetListInterface
{
    /**
     * Get job list
     *
     * @param SearchCriteriaInterface|null $searchCriteria
     * @return \Firebear\ImportExport\Api\Import\SearchResultsInterface
     */
    public function execute(SearchCriteriaInterface $searchCriteria = null);
}
