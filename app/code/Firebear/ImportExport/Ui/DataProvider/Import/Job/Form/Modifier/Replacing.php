<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\DataProvider\Import\Job\Form\Modifier;

use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Firebear\ImportExport\Model\Import\Replacement\Option\AttributePool;

/**
 * Data provider for replacing form
 */
class Replacing implements ModifierInterface
{
    /**
     * Attribute pool
     *
     * @var AttributePool
     */
    private $attributePool;

    /**
     * @param AttributePool $attributePool
     */
    public function __construct(
        AttributePool $attributePool
    ) {
        $this->attributePool = $attributePool;
    }

    /**
     * {@inheritdoc}
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
        $meta['source_data_replacing_container'] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'valuesForOptions' => $this->getAllOptions()
                    ]
                ]
            ]
        ];
        return $meta;
    }

    /**
     * {@inheritdoc}
     */
    private function getAllOptions()
    {
        $options = [];
        foreach ($this->attributePool->getAllOptions() as $code => $option) {
            $options[$code] = $code;
        }
        return $options;
    }
}
