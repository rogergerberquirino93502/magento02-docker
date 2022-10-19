<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\ResourceModel\Review;

use Magento\Review\Model\ResourceModel\Review\Collection as AbstractCollection;
use Magento\Review\Model\Review;

/**
 * Review collection
 */
class Collection extends AbstractCollection
{
    /**
     * Product table name
     *
     * @var string
     */
    protected $productTable;

    /**
     * Initialize select
     *
     * @return $this
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $this->getSelect()->join(
            ['p' => $this->getProductTable()],
            'main_table.entity_pk_value = p.entity_id',
            ['p.sku']
        )->join(
            ['e' => $this->getReviewEntityTable()],
            'main_table.entity_id = e.entity_id',
            []
        )->where('e.entity_code = ?', Review::ENTITY_PRODUCT_CODE);
        return $this;
    }

    /**
     * Retrieve product table
     *
     * @return string
     */
    protected function getProductTable()
    {
        if ($this->productTable === null) {
            $this->productTable = $this->getTable('catalog_product_entity');
        }
        return $this->productTable;
    }
}
