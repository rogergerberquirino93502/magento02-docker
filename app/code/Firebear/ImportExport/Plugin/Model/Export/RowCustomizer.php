<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio GmbH. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */

/**
 * Plugin for config class.
 * Replace default magento classes.
 */

namespace Firebear\ImportExport\Plugin\Model\Export;

/**
 * Class RowCustomizer
 *
 * @package Firebear\ImportExport\Plugin\Model\Export
 */
class RowCustomizer
{
    /**
     * @param \Magento\ConfigurableImportExport\Model\Export\RowCustomizer $model
     * @param \Closure $work
     * @param $collection
     * @param $productIds
     */
    public function aroundPrepareData(
        \Magento\ConfigurableImportExport\Model\Export\RowCustomizer $model,
        \Closure $work,
        $collection,
        $productIds
    ) {
        $newCollection = clone $collection;
        $newCollection->setCurPage(0);
        $work($newCollection, $productIds);
    }
}
