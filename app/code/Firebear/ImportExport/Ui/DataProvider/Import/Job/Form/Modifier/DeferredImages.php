<?php

namespace Firebear\ImportExport\Ui\DataProvider\Import\Job\Form\Modifier;

use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Ui\Component\Form;
use Magento\Framework\App\ProductMetadataInterface;

/**
 * Class DeferredImages
 * @package Firebear\ImportExport\Ui\DataProvider\Import\Job\Form\Modifier
 */
class DeferredImages implements ModifierInterface
{
    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * DeferredImages constructor.
     * @param ProductMetadataInterface $productMetadata
     */
    public function __construct(ProductMetadataInterface $productMetadata)
    {
        $this->productMetadata = $productMetadata;
    }

    /**
     * @param array $data
     * @return array
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        return $this->addQueueField($meta);
    }

    /**
     * @param $meta
     * @return mixed
     */
    protected function addQueueField($meta)
    {
        $versionNoAmqp = !version_compare($this->productMetadata->getVersion(), '2.3.0', '>=')
            && $this->productMetadata->getEdition() == 'Community';
        if ($versionNoAmqp) {
            return $meta;
        }

        $meta['source']['children']['deferred_images'] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'dataType' => Form\Element\DataType\Number::NAME,
                        'formElement' => Form\Element\Checkbox::NAME,
                        'componentType' => Form\Field::NAME,
                        'prefer' => 'toggle',
                        'valueMap' => [
                            'false' => '0',
                            'true' => '1'
                        ],
                        'sortOrder' => 91,
                        'label' => __('Deferred Import Images'),
                        'description' => __('Deferred Import Images'),
                        'source' => 'import1',
                        'default' => '0',
                        'tooltip' => [
                            'description' => __('Product images are added to the queue, and are imported AFTER
                             the product data has been imported to the store. Helps split import into parts.'),
                        ],
                    ]
                ],
            ]
        ];

        return $meta;
    }
}
