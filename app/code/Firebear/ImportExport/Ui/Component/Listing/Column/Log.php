<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Backend\Model\UrlInterface;

/**
 * Class Options
 */
class Log extends Column
{

    /**
     * @var Filesystem\Directory\ReadInterface
     */
    protected $directory;

    /**
     * @var UrlInterface
     */
    protected $backendUrl;

    /**
     * Log constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param Filesystem $filesystem
     * @param UrlInterface $backendUrl
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Filesystem $filesystem,
        UrlInterface $backendUrl,
        array $components = [],
        array $data = []
    ) {
        $this->directory = $filesystem->getDirectoryRead(DirectoryList::LOG);
        $this->backendUrl = $backendUrl;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $item['file'] = $this->prepareItem($item);
            }
        }
        return $dataSource;
    }

    /**
     * @param $item
     * @return string
     */
    public function prepareItem($item)
    {
        if (!empty($item['db_log_storage'])) {
            $urlPath = $this->getData('config/urlPathForDbStorage') ?: '#';
            $jobType = $this->getData('config/jobType') ?: 'import';
            $path = $this->backendUrl->getUrl($urlPath, ['history_id' => $item['history_id'], 'job_type' => $jobType]);
            return '<a href="' . $path . '">' . __('Download') . '</a>';
        } elseif ($this->directory->isFile('/firebear/' . $item['file'] . '.log')) {
            $urlPath = $this->getData('config/urlPath') ?: '#';
            $path = $this->backendUrl->getUrl($urlPath, ['file' => $item['file']]);
            return '<a href="' . $path . '">' . __('Download') . '</a>';
        } else {
            return __('File doesn\'t exist or not found');
        }
    }
}
