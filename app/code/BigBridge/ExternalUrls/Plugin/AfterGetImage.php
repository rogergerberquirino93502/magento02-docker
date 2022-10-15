<?php

namespace BigBridge\ExternalUrls\Plugin;

use Magento\Catalog\Block\Product\AbstractProduct;

class AfterGetImage
{

    /**
     * AfterGetImage constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param AbstractProduct $subject
     * @param $result
     * @param $product
     * @param $imageId
     * @param $attributes
     * @return mixed
     */
    //"https://m.media-amazon.com/images/I/71z6gsI87bL._AC_SL1500_.jpg";
    public function afterGetImage(AbstractProduct $subject, $result)
    {
       try{
        if ($product) {
            $image = array();
            $image['image_url'] = "http://media.maxicompra.com/image/producto/07j/1wz/8pd/B07J1WZ8PD.jpg";
            $image['width'] = "240";
            $image['height'] = "300";
            $image['label'] = $product->getName();
            $image['ratio'] = "1.25";
            $image['custom_attributes'] = "";
            $image['resized_image_width'] = "399";
            $image['resized_image_height'] = "399";
            $image['product_id'] = $product->getId();
    if ($image) {
        $result->setData($image);
    }
}
         }catch (\Exception $e){
         }
              return $result;
    }
}
