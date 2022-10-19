<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column;

use Firebear\ImportExport\Ui\Component\Listing\Column\ExportSource\Options;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class ExportSource
 *
 * @package Firebear\ImportExport\Ui\Component\Listing\Column
 */
class ExportSource extends Column
{
    /**
     * @var Options
     */
    protected $options;

    /**
     * ExportSource constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param Options $options
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Options $options,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->options = $options;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $item[$this->getData('name')] = $this->prepareItem($item);
            }
        }

        return $dataSource;
    }

    /**
     * @param $item
     *
     * @return null
     */
    protected function prepareItem($item)
    {
        $result = null;
        $list = $this->options->toArray();
        $source = $item['export_source'];
        if (isset($source['export_source_entity']) && isset($list[$source['export_source_entity']])) {
            $result = $list[$source['export_source_entity']];
        }

        return $result;
    }
}
