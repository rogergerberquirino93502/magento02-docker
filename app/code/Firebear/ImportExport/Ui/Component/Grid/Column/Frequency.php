<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Grid\Column;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use Firebear\ImportExport\Model\Job;

/**
 * Class Frequency
 *
 * @package Firebear\ImportExport\Ui\Component\Grid\Column
 */
class Frequency extends Column
{

    /**
     * @var \Firebear\ImportExport\Model\Job
     */
    protected $jobModel;

    /**
     * @param ContextInterface   $context
     * @param UiComponentFactory $uiComponentFactory
     * @param Job                $jobModel
     * @param array              $components
     * @param array              $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Job $jobModel,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->jobModel = $jobModel;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            $frequencyModes = $this->jobModel->getExtendedFrequencyModes();
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[$fieldName])) {
                    $mode = $item[$fieldName];
                    $value = $frequencyModes[$mode]['title'] ?? '';
                    $item[$fieldName] = $value;
                }
            }
        }

        return $dataSource;
    }
}
