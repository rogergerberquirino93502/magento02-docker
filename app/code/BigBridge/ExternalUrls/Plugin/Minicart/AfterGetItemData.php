<?php

namespace BigBridge\ExternalUrls\Plugin\Minicart;

use Magento\Checkout\CustomerData\AbstractItem;

class AfterGetItemData
{
    /**
     * AfterGetImageData constructor.
     */
    public function __construct(
    )
    {
    }
    /**
     * @param AbstractItem $item
     * @param $result
     * @return mixed
     */
    public function afterGetItemData(AbstractItem $item, $result)
    {
        try {
            if ($result['product_id'] > 0) {
                $image = "http://media.maxicompra.com/image/producto/07j/1wz/8pd/B07J1WZ8PD.jpg";
                $result['product_image']['src'] = $image;
            }
        } catch (\Exception $e) {
        }
        return $result;
    }
}
