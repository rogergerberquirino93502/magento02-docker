<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Plugin\Block\Adminhtml\Import\Edit;

use Magento\Backend\Model\UrlInterface;

/**
 * Class Before
 *
 * @package Firebear\ImportExport\Plugin\Block\Adminhtml\Import\Edit
 */
class Before
{
    /**
     * @var UrlInterface
     */
    protected $url;

    /**
     * Before constructor.
     *
     * @param UrlInterface $backendUrl
     */
    public function __construct(
        UrlInterface $backendUrl
    ) {
        $this->url = $backendUrl;
    }

    /**
     * @param \Magento\ImportExport\Block\Adminhtml\Import\Edit\Before $subject
     * @param \Closure $work
     * @return string
     */
    public function aroundToHtml(
        \Magento\ImportExport\Block\Adminhtml\Import\Edit\Before $subject,
        \Closure $work
    ) {
        $html = '<div id="messages"><div class="messages">';
        $html .= '<div class="message message-notice"><div>' .
            __(
                'You have installed and enabled FireBear Studio Improved Import / Export extension - '.
                'to use it please go to System -> %1 / %2. We don\'t improve and not make any changes on '.
                'default Magento 2 Import / Export behaviour!',
                "<a href=" . $this->url->getUrl('import/job/index') . ">Import Jobs</a>",
                "<a href=" . $this->url->getUrl('import/export_job/index') . ">Export Jobs</a>"
            ) . '</div></div>';
        $html .= '</div></div>';

        return $html . $work();
    }
}
