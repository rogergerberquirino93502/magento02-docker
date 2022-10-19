<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Source\Config;

use Magento\Config\Model\Config\Source\Email\Template as AbstractTemplate;

/**
 * Template source
 */
class Template extends AbstractTemplate
{
    /**
     * Generate list of email templates
     *
     * @return array
     */
    public function toOptionArray()
    {
        $this->setPath('import_export_notification_email');

        return parent::toOptionArray();
    }
}
