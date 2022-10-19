<?php
/**
 * ImageTypeProcessor
 *
 * @copyright Copyright © 2019 Firebear Studio. All rights reserved.
 * @author    fbeardev@gmail.com
 */

namespace Firebear\ImportExport\Plugin\Model\Import\Product;

use Firebear\ImportExport\Model\Export\RowCustomizer\ProductVideo;
use Magento\CatalogImportExport\Model\Import\Product\ImageTypeProcessor as MagentoImageTypeProcessor;

class ImageTypeProcessor
{
    /**
     * @param MagentoImageTypeProcessor $subject
     * @param $data
     * @return array
     */
    public function afterGetImageTypes(
        MagentoImageTypeProcessor $subject,
        $data
    ) {
        return array_merge($data, [ProductVideo::VIDEO_URL_COLUMN]);
    }
}
