<?php
/**
 * MageArrayMarketplace
 *
 * @copyright Copyright © 2019 Firebear Studio. All rights reserved.
 * @author    fbeardev@gmail.com
 */

namespace Firebear\ImportExport\Model\Export\RowCustomizer\Integrations\Modules;

use MageArray\MaMarketPlace\Helper\Data as MaMarketPlaceHelperData;

class MageArrayMarketplace extends WebkulMarketplace
{
    const VENDOR_ID = 'magearray_vendor_id';
    const MODULE_NAME = 'MageArray_MaMarketPlace';

    protected $vendorData = [];
    /**
     * @var MaMarketPlaceHelperData
     */
    private $mageArrayHelper;

    /**
     * Prepare data for export
     *
     * @param mixed $collection
     * @param int[] $productIds
     * @return mixed
     */
    public function prepareData($collection, $productIds)
    {
        if (empty($this->vendorData) && $this->isModuleEnabled()) {
            $this->mageArrayHelper = $this->getObjectManager()->get(MaMarketPlaceHelperData::class);
            foreach ($productIds as $productId) {
                $this->vendorData[$productId] = $this->mageArrayHelper->getVendorByProductId($productId)->getUserId();
            }
        }
        return $this;
    }
}
