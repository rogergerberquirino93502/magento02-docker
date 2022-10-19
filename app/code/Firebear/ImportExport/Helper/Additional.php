<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Helper;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Firebear\ImportExport\Model\Source\Factory;

/**
 * Class Additional
 * @package Firebear\ImportExport\Helper
 */
class Additional extends AbstractHelper
{
    /**
     * @var Factory
     */
    protected $sourceFactory;

    protected $sourceTypeConfig;

    /**
     * Additional constructor.
     * @param Context $context
     * @param Factory $sourceFactory
     */
    public function __construct(
        Context $context,
        Factory $sourceFactory,
        \Firebear\ImportExport\Model\Source\Config $sourceTypeConfig
    ) {
        $this->sourceFactory = $sourceFactory;
        $this->sourceTypeConfig = $sourceTypeConfig;
        parent::__construct($context);
    }

    /**
     * Prepare source type class name
     *
     * @param string $sourceType
     *
     * @return string
     */
    protected function prepareSourceClassName($sourceType)
    {
        $config = $this->sourceTypeConfig->get();
        if (isset($config[$sourceType])) {
            return $config[$sourceType]['model'];
        }

        return '';
    }

    /**
     * Get source model by source type
     *
     * @param string $sourceType
     *
     * @return \Firebear\ImportExport\Model\Source\Type\AbstractType
     * @throws LocalizedException
     */
    public function getSourceModelByType($sourceType)
    {
        $sourceClassName = $this->prepareSourceClassName($sourceType);
        if ($sourceClassName && class_exists($sourceClassName)) {
            /** @var \Firebear\ImportExport\Model\Source\Type\AbstractType $source */
            $source = $this->getSourceFactory()->create($sourceClassName);

            return $source;
        } else {
            throw new LocalizedException(
                __("Import source type class for '" . $sourceType . "' is not exist.")
            );
        }
    }

    /**
     * Get source factory
     *
     * @return Factory
     */
    public function getSourceFactory()
    {
        return $this->sourceFactory;
    }
}
