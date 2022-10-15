<?php
namespace BigBridge\ExternalUrls\Plugin;

use Magento\Catalog\Block\Product\View\Gallery;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\CollectionFactory;
use Magento\Framework\DataObject;

class AddImagesToGalleryBlock{
    /**
     * @var CollectionFactory
     */
    protected $dataCollectionFacyory;
    /**
     * AddImagesToGalleryBlock constructor.
     *
     * @param CollectionFactory $dataCollectionFactory
     */
    public function __construct(
        CollectionFactory $dataCollectionFactory
    ){
        $this->dataCollectFactory = $dataCollectionFactory;
    }
        /**
     * afterGalleryImages Plugin to change images and use external images stored in custom attribute
     *
     * @param Gallery $subject
     * @param Collection|null $images
     * @return Collection|null
     */
    public function afterGetGalleryImages(Gallery $subject, $images){
        try{
            $hasExternalImages = false;
             if($hasExternalImages){
                return $images;
             }
             $product = $subject->getProduct();
             $images = $this->dataCollectFactory->create();
             $productName = $product->getName();
             //metodo que obtiene las imagenes url externas importadas en CSV y la guarda en un atributo del producto
            $externalImages = ["http://media.maxicompra.com/image/producto/07j/1wz/8pd/B07J1WZ8PD.jpg"];

     
             //get images from custom attribute
             foreach ($externalImages as $item) {
                $imageId = uniqid();
                $small = $item;
                $medium = $item;
                $large = $item;
                $image = [
                    'file' => $large,
                    'media_type' => 'image',
                    'value_id' => $imageId, // unique value
                    'row_id' => $imageId, // unique value
                    'label' => $productName,
                    'label_default' => $productName,
                    'position' => 100,
                    'position_default' => 100,
                    'disabled' => 0,
                    'url'  => $large,
                    'path' => '',
                    'small_image_url' => $small,
                    'thumbnall_image_url' => $medium,
                    'large_image_url' => $large
                ];
                $images->addItem(new DataObject($image));
            }
                return $images;
                    }catch(\Exception $e){
                return $images;
        }
    }
}
