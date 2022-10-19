<?php

namespace Firebear\ImportExport\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class Options
 */
class LogStorageType extends Column
{

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $item['storage_type'] = $this->getStorageTypeValue($item);
            }
        }
        return $dataSource;
    }

    /**
     * @param array $item
     * @return string
     */
    private function getStorageTypeValue(array $item)
    {
        return !empty($item['db_log_storage']) ? 'database' : 'files';
    }
}
