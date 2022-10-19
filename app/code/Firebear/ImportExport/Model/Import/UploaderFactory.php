<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import;

use Magento\CatalogImportExport\Model\Import\UploaderFactory as NativeUploaderFactory;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class UploaderFactory
 *
 * @package Firebear\ImportExport\Model\Import
 */
class UploaderFactory extends NativeUploaderFactory
{
    /**
     * UploaderFactory constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param string $instanceName
     *
     * phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        $instanceName = Uploader::class
    ) {
        // phpcs:enable
        parent::__construct($objectManager, $instanceName);
    }
}
