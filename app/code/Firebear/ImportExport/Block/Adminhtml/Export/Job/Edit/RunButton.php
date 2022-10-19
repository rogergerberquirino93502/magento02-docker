<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Block\Adminhtml\Export\Job\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Class DeleteButton
 */
class RunButton extends GenericButton implements ButtonProviderInterface
{

    /**
     * @return array
     */
    public function getButtonData()
    {
        $data = [];
        $data = [
            'label' => __('Save & Run'),
            'class' => 'action-primary',
            'on_click' => '',
            'data_attribute' => [
                'mage-init' => [
                    'button' => ['event' => 'saveAndRun']
                ],
            ],
            'sort_order' => 20,
        ];

        return $data;
    }
}
