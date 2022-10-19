<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Api\Export;

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
     * @return \Firebear\ImportExport\Api\Data\ExportInterface[]
     */
    public function getItems();

    /**
     * Set jobs list
     *
     * @param \Firebear\ImportExport\Api\Data\ExportInterface[] $items
     * @return void
     */
    public function setItems(array $items);
}
