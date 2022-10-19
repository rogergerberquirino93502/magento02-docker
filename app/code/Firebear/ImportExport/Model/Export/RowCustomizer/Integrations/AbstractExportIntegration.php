<?php
/**
 * AbstractExportIntegration
 *
 * @copyright Copyright © 2019 Firebear Studio. All rights reserved.
 * @author    fbeardev@gmail.com
 */

namespace Firebear\ImportExport\Model\Export\RowCustomizer\Integrations;

use Magento\CatalogImportExport\Model\Export\RowCustomizerInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface as ObjectManager;

/**
 * Class AbstractExportIntegration
 * @package Firebear\ImportExport\Model\Export\RowCustomizer\Integrations
 */
abstract class AbstractExportIntegration implements RowCustomizerInterface
{
    const MODULE_NAME = '';

    /**
     * @var Manager
     */
    private $manager;
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * AbstractExportIntegration constructor.
     * @param Manager $manager
     * @param ObjectManager $objectManager
     */
    public function __construct(
        Manager $manager,
        ObjectManager $objectManager
    ) {
        $this->manager = $manager;
        $this->objectManager = $objectManager;
    }

    /**
     * @return ObjectManager
     */
    public function getObjectManager(): ObjectManager
    {
        return $this->objectManager;
    }

    /**
     * @return bool
     */
    protected function isModuleEnabled(): bool
    {
        return static::MODULE_NAME === '' ? false : $this->getModuleManager()->isEnabled(static::MODULE_NAME);
    }

    /**
     * @return Manager
     */
    public function getModuleManager(): Manager
    {
        return $this->manager;
    }
}
