<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Grid\Column;

use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class Cron
 *
 * @package Firebear\ImportExport\Ui\Component\Grid\Column
 */
class Cron extends Column
{
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
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[$fieldName])) {
                    $schedule = explode(' ', $item[$fieldName]);
                    $value = sprintf(
                        '%s %s %s %s %s',
                        $schedule[0],
                        $schedule[1],
                        $schedule[2],
                        $schedule[3],
                        $schedule[4]
                    );
                    $item[$fieldName] = $value;
                }
            }
        }

        return $dataSource;
    }
}
