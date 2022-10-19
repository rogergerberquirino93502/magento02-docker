<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Api\Export\History;

use Magento\Framework\Api\SearchResultsInterface as AbstractSearchResultsInterface;

/**
 * Search results of repository::getList method
 *
 * @api
 */
interface SearchResultsInterface extends AbstractSearchResultsInterface
{
    /**
     * Get histories list
     *
     * @return \Firebear\ImportExport\Api\Data\ExportHistoryInterface[]
     */
    public function getItems();

    /**
     * Set histories list
     *
     * @param \Firebear\ImportExport\Api\Data\ExportHistoryInterface[] $items
     * @return void
     */
    public function setItems(array $items);
}
