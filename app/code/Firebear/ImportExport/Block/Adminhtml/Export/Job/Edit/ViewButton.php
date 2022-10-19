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
class ViewButton extends GenericButton implements ButtonProviderInterface
{

    /**
     * @return array
     */
    public function getButtonData()
    {
        $data = [];
        $data = [
            'label' => __('View History'),
            'class' => '',
            'on_click' => '',
            'data_attribute' => [
                'mage-init' => [
                    'Magento_Ui/js/form/button-adapter' => [
                        'actions' => [
                            [
                                "targetName" => "import_export_job_form.import_export_job_form.history_export",
                                "actionName" => "toggleModal",
                                'params' => [
                                    true,
                                    ['auto_apply' => 1],
                                ]
                            ]
                        ]
                    ]
                ],

            ],
            'sort_order' => 20,
        ];

        return $data;
    }
}
