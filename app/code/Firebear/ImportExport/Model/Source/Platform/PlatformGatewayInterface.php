<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Platform;

/**
 * Gateway interface
 */
interface PlatformGatewayInterface
{
    /**
     * Retrieve import source
     *
     * @return \Magento\ImportExport\Model\Import\AbstractSource
     */
    public function getSource();
}
