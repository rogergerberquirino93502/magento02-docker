<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Product;

use Magento\CatalogImportExport\Model\Import\Product\OptionFactory as NativeOptionFactory;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class OptionFactory
 *
 * @package Firebear\ImportExport\Model\Import\Product
 */
class OptionFactory extends NativeOptionFactory
{
    /**
     * OptionFactory constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param string $instanceName
     *
     * phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        $instanceName = Option::class
    ) {
        // phpcs:enable
        parent::__construct($objectManager, $instanceName);
    }
}
