<?php
namespace BigBridge\ExternalUrls\Ui\Component\Listing\Column;

use Magento\Catalog\Helper\Image;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class Thumbnail
 *
 * @api
 * @since 100.0.2
 */
class Thumbnail extends Column
{
    const NAME = 'thumbnail';

    const ALT_FIELD = 'name';

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param Image $imageHelper
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Image $imageHelper,
        \Magento\Framework\UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->imageHelper = $imageHelper;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        try {
            if (isset($dataSource['data']['items'])) {
                $fieldName = $this->getData('name');
                foreach ($dataSource['data']['items'] as & $item) {
                    $product = new DataObject($item);
                    //metodo que obtiene las imagenes url externas importadas en CSV y la guarda en un atributo del producto
                    $imageUrl = "https://m.media-amazon.com/images/I/71z6gsI87bL._AC_SL1500_.jpg";
                    if ($imageUrl != "") {
                        $item[$fieldName . '_src'] = $imageUrl;
                        $item[$fieldName . '_alt'] = $this->getAlt($item) ?: $product->getName();
                        $item[$fieldName . '_link'] = $this->urlBuilder->getUrl(
                            'catalog/product/edit',
                            ['id' => $product->getEntityId(), 'store' => $this->context->getRequestParam('store')]
                        );
                        $item[$fieldName . '_orig_src'] = $imageUrl;

                    } else {
                        $imageHelper = $this->imageHelper->init($product, 'product_listing_thumbnail');
                        $item[$fieldName . '_src'] = $imageHelper->getUrl();
                        $item[$fieldName . '_alt'] = $this->getAlt($item) ?: $imageHelper->getLabel();
                        $item[$fieldName . '_link'] = $this->urlBuilder->getUrl(
                            'catalog/product/edit',
                            ['id' => $product->getEntityId(), 'store' => $this->context->getRequestParam('store')]
                        );
                        $origImageHelper = $this->imageHelper->init($product, 'product_listing_thumbnail_preview');
                        $item[$fieldName . '_orig_src'] = $origImageHelper->getUrl();
                    }
                }
            }
        } catch (\Exception $e) {
        }

        return $dataSource;
    }

    /**
     * Get Alt
     *
     * @param array $row
     *
     * @return null|string
     */
    protected function getAlt($row)
    {
        $altField = $this->getData('config/altField') ?: self::ALT_FIELD;
        return $row[$altField] ?? null;
    }
}
