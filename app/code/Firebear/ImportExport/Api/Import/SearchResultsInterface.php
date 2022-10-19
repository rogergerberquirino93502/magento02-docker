<?php
/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Api\Import;

use Magento\Framework\Api\SearchResultsInterface as AbstractSearchResultsInterface;

/**
 * Search results of repository::getList method
 *
 * @api
 */
interface SearchResultsInterface extends AbstractSearchResultsInterface
{
    /**
     * Get jobs list
     *
     * @return \Firebear\ImportExport\Api\Data\ImportInterface[]
     */
    public function getItems();

    /**
     * Set jobs list
     *
     * @param \Firebear\ImportExport\Api\Data\ImportInterface[] $items
     * @return void
     */
    public function setItems(array $items);
}
