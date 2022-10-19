<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import;

use Magento\Framework\Module\Dir\Reader;

/**
 * Class Platforms
 *
 * @package Firebear\ImportExport\Model\Import
 */
class Platforms extends \Magento\Framework\DataObject
{
    const URL_DOWNLOAD = "import/job/download";
    const GITHUB_LINK = "https://github.com/firebearstudio/magento2-import-export-sample-files";

    /**
     * @var Reader
     */
    protected $moduleReader;

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $backendUrl;

    /**
     * @var array|mixed|null
     */
    protected $platforms;

    /**
     * Platforms constructor.
     * @param Reader $moduleReader
     * @param \Magento\Backend\Model\UrlInterface $backendUrl
     * @param \Firebear\ImportExport\Model\Source\Platform\Config $config
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Module\Dir\Reader $moduleReader,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Firebear\ImportExport\Model\Source\Platform\Config $config,
        array $data = []
    ) {
        parent::__construct($data);
        $this->moduleReader = $moduleReader;
        $this->backendUrl = $backendUrl;
        $this->platforms = $config->get();
    }

    /**
     * @return array
     */
    public function toOptionArrayLinks($entityType = 'catalog_product')
    {
        $list = [];
        $platforms = $this->platforms[$entityType] ?? [];
        foreach ($platforms as $platform => $data) {
            if (isset($data['links'])) {
                foreach ($data['links'] as $link) {
                    if (isset($link['entity']) && $link['entity'] === 'github_link') {
                        $list[] = [
                            'label' => __($link['label']),
                            'href' => self::GITHUB_LINK,
                            'type' => $platform,
                            'entity' => isset($link['entity']) ? $link['entity'] : ''
                        ];
                    } else {
                        $list[] = [
                            'label' => __($link['label']),
                            'href' => $this->backendUrl->getUrl(
                                self::URL_DOWNLOAD,
                                ['type' => $platform . $link['suffix']]
                            ),
                            'type' => $platform,
                            'entity' => isset($link['entity']) ? $link['entity'] : ''
                        ];
                    }
                }
            }
        }
        return $list;
    }

    /**
     * @return array
     */
    public function toOptionArrayNames()
    {
        return [];
    }

    /**
     * @return array
     */
    public function toOptionArrayButton()
    {
        $list = [];
        foreach ($this->platforms as $platform => $data) {
            $list[$platform] = [];
            if (isset($data['fields'])) {
                foreach ($data['fields'] as $name => $value) {
                    $list[$platform][] = ['label' => $value['reference'], 'value' => $name];
                }
            }
        }

        return $list;
    }

    /**
     * @param $type
     * @return array
     */
    public function getAllData($type)
    {
        $list = [];
        foreach ($this->platforms as $platform => $data) {
            if ($platform == $type) {
                if (isset($data['fields'])) {
                    foreach ($data['fields'] as $name => $value) {
                        $list[$name] = ['reference' => $value['reference'],'default' => $value['default']];
                    }
                }
            }
        }

        return $list;
    }

    /**
     * @param $entityType
     * @return array
     */
    public function getPlatformList($entityType)
    {
        $options = [];
        $platforms = $this->platforms[$entityType] ?? [];
        foreach ($platforms as $platform => $data) {
            $options[] = [
                'label' => __($data['label']),
                'value' => $platform
            ];
        }
        return ['options' => $options];
    }
}
