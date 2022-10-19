<?php
/**
 * @copyright: Copyright © 2018 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Form\System;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Apply options for "Price Rules" fieldset
 */
class Apply implements OptionSourceInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            ['label' => 'Percent', 'value' => 'percent'],
            ['label' => 'Fixed', 'value' => 'fixed']
        ];
    }
}
