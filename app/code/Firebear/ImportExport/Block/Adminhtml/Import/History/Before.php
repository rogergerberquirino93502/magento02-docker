<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Block\Adminhtml\Import\History;

/**
 * Class Before
 *
 * @package Firebear\ImportExport\Block\Adminhtml\Import\History
 */
class Before extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    public $url;

    /**
     * Before constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Model\UrlInterface $url
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Model\UrlInterface $url,
        array $data = []
    ) {
        $this->url = $url;
        parent::__construct($context, $data);
    }
}
