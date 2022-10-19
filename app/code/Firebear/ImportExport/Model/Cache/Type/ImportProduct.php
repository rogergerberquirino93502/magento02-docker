<?php

namespace Firebear\ImportExport\Model\Cache\Type;

use Magento\Framework\Cache\Frontend\Decorator\TagScope;
use Magento\Framework\App\Cache\Type\FrontendPool;

/**
 * Class ImportProduct
 * @package Firebear\ImportExport\Model\Cache\Type
 */
class ImportProduct extends TagScope
{
    const TYPE_IDENTIFIER = 'import_pr';

    const CACHE_TAG = 'IMPORT_PR';

    const BUFF_CACHE = 'FB_IMPORT_PR_BUFF';

    const ROW_SKUS_CACHE_ID = 'row_configurable_product_skus_';

    /**
     * @param FrontendPool $cacheFrontendPool
     */
    public function __construct(FrontendPool $cacheFrontendPool)
    {
        parent::__construct(
            $cacheFrontendPool->get(self::TYPE_IDENTIFIER),
            self::CACHE_TAG
        );
    }
}
