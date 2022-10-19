<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Block\Adminhtml;

/**
 * Class Menu
 *
 * @package Firebear\ImportExport\Block\Adminhtml
 */
class Menu extends \Magento\Backend\Block\Template
{
    /**
     * @var string
     */
    protected $_template = 'Firebear_ImportExport::menu.phtml';

    /**
     * @var \Firebear\ImportExport\Model\Source\Menu\Config
     */
    protected $config;

    /**
     * @var \Magento\Framework\Module\PackageInfo
     */
    protected $packageInfo;

    /**
     * Menu constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Firebear\ImportExport\Model\Source\Menu\Config $config
     * @param \Magento\Framework\Module\PackageInfo $packageInfo
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Firebear\ImportExport\Model\Source\Menu\Config $config,
        \Magento\Framework\Module\PackageInfo $packageInfo,
        array $data = []
    ) {
        $this->config = $config;
        $this->packageInfo = $packageInfo;
        parent::__construct($context, $data);
    }

    /**
     * @return array|mixed|null
     */
    public function getItems()
    {
        return $this->config->get();
    }

    public function getVersion()
    {
        return  $this->packageInfo->getVersion('Firebear_ImportExport');
    }
}
