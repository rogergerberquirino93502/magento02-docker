<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export\Product;

/**
 * Interface AdditionalFields
 * @package Firebear\ImportExport\Model\Export\Product
 */
interface AdditionalFieldsInterface
{
    /**
     * @param array $rows
     * @return $this
     */
    public function addFields(array &$rows);

    /**
     * @return array
     */
    public function getHeaders(): array;
}
