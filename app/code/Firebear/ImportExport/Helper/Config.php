<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
declare(strict_types=1);

namespace Firebear\ImportExport\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

/**
 * Config helper
 */
class Config extends AbstractHelper
{
    /**
     * Enable encryptor config path
     */
    const ENCRYPTION_CONF_PATH = 'firebear_importexport/general/encryption';

    /**
     * Check encryption should be enabled
     *
     * @return bool
     */
    public function isEncryption()
    {
        return $this->scopeConfig->isSetFlag(self::ENCRYPTION_CONF_PATH);
    }
}
