<?php
namespace BigBridge\ExternalUrls\Plugin;

use Magento\Catalog\Block\Product\Image;

class AfterGetImageUrl
{
     /**
     * AfterGetImage constructor.
     */
    public function __construct(
        )
        {
        }

        /**
         * @param Image $image
         * @param $method
         * @return array|null
         */
        public function after__call(Image $image, $result, $method)
        {
            try {
                if ($method == 'getImageUrl' && $image->getProductId() > 0) {
                    $result = "http://media.maxicompra.com/image/producto/07j/1wz/8pd/B07J1WZ8PD.jpg";
                }
            } catch (\Exception $e) {
            }
            return $result;
        }

}
